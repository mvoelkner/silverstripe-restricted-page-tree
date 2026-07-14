<?php

namespace MVoelkner\RestrictedPageTree;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Adds the CanViewType/CanEditType/ViewerGroups/EditorGroups fields File already knows
 * how to use (see File::canView()/canEdit()), and layers two rules on top for members
 * of a group flagged via GroupFileRestrictionExtension::UseOwnFileFolder:
 *
 * - Anything outside their own folder (and its subfolders) is explicitly denied, even
 *   though the file itself might otherwise be openly accessible to any CMS user.
 *   Access *within* their own folder is left to the core permission fields set on that
 *   folder - no extra "allow" logic is needed here.
 * - Inside their own folder subtree, only a fixed set of safe file extensions may be
 *   uploaded, and subfolder names starting with "." are rejected.
 *
 * Members with ADMIN or File::EDIT_ALL are never restricted.
 *
 * @extends Extension<File>
 */
class RestrictedFileAccessExtension extends Extension
{
    private const SAFE_EXTENSIONS = [
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'txt', 'csv',
    ];

    private static $db = [
        'CanViewType' => 'Enum(array("Inherit", "Anyone", "LoggedInUsers", "OnlyTheseUsers"), "Inherit")',
        'CanEditType' => 'Enum(array("Inherit", "LoggedInUsers", "OnlyTheseUsers"), "Inherit")',
    ];

    private static $many_many = [
        'ViewerGroups' => Group::class,
        'EditorGroups' => Group::class,
    ];

    public function canView($member = null)
    {
        return $this->denyOutsideOwnFolder($member, $this->getOwner());
    }

    public function canEdit($member = null)
    {
        return $this->denyOutsideOwnFolder($member, $this->getOwner());
    }

    public function canDelete($member = null)
    {
        return $this->denyOutsideOwnFolder($member, $this->getOwner());
    }

    public function canCreate($member = null, $context = [])
    {
        $target = $context['Parent'] ?? $this->getOwner();

        return $this->denyOutsideOwnFolder($member, $target);
    }

    public function updateValidate(ValidationResult $result)
    {
        $file = $this->getOwner();
        $member = Security::getCurrentUser();
        $ownedFolderIDs = $member ? $this->getOwnedFolderIDs($member) : [];

        if ($ownedFolderIDs === [] || !$this->isWithinFolders($file, $ownedFolderIDs)) {
            return;
        }

        if ($file instanceof Folder) {
            if (str_starts_with($file->Name ?? '', '.')) {
                $result->addError('Folder names starting with "." are not allowed.');
            }

            return;
        }

        $extension = strtolower((string) $file->getExtension());

        if (!in_array($extension, self::SAFE_EXTENSIONS, true)) {
            $result->addError(sprintf('Files of type ".%s" are not allowed in this folder.', $extension));
        }
    }

    /**
     * Returns null (defer to core) unless the current member is restricted to their own
     * folder and the given target lies outside it - in which case access is explicitly denied.
     */
    private function denyOutsideOwnFolder($member, ?File $target): ?bool
    {
        $member = $member ?: Security::getCurrentUser();

        if (!$member || !$target) {
            return null;
        }

        if (Permission::checkMember($member, ['ADMIN', File::EDIT_ALL])) {
            return null;
        }

        $ownedFolderIDs = $this->getOwnedFolderIDs($member);

        if ($ownedFolderIDs === []) {
            return null;
        }

        return $this->isWithinFolders($target, $ownedFolderIDs) ? null : false;
    }

    private function getOwnedFolderIDs(Member $member): array
    {
        $ids = Group::get()
            ->filter([
                'ID' => $member->Groups()->column('ID'),
                'UseOwnFileFolder' => true,
            ])
            ->exclude('OwnFileFolderID', 0)
            ->column('OwnFileFolderID');

        return array_map('intval', $ids);
    }

    private function isWithinFolders(File $file, array $folderIDs): bool
    {
        $current = $file;
        $depth = 0;

        while ($current && $depth < 50) {
            if ($current->ID && in_array((int) $current->ID, $folderIDs, true)) {
                return true;
            }

            $current = $current->ParentID ? $current->Parent() : null;
            $depth++;
        }

        return false;
    }
}
