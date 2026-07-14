<?php

namespace MVoelkner\RestrictedPageTree;

use SilverStripe\Assets\Folder;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Group;

/**
 * Gives a group its own restricted file folder: when "UseOwnFileFolder" is ticked,
 * a folder named "restricted-files-<group-code>" is created (once) on save, locked
 * to "Only these people" for both view and edit, with this group as viewer/editor.
 *
 * @extends Extension<Group>
 */
class GroupFileRestrictionExtension extends Extension
{
    private static $db = [
        'UseOwnFileFolder' => 'Boolean',
    ];

    private static $has_one = [
        'OwnFileFolder' => Folder::class,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Permissions',
            CheckboxField::create(
                'UseOwnFileFolder',
                'Use own restricted file folder'
            )->setDescription(
                'Creates a dedicated folder for this group in the "Files" section on save '
                . '(named after the group\'s code) and restricts members of this group to '
                . 'that folder: they can upload, edit and delete files there (including in '
                . 'subfolders they create), but cannot view, edit or create anything outside '
                . 'it. Only common image and document file types are allowed inside the '
                . 'folder, and subfolder names starting with "." are rejected. Does not apply '
                . 'to members with full administrative rights. Members of this group still '
                . 'need the "Access to \'Files\' section" permission to reach the Files area '
                . 'at all.'
            )
        );
    }

    public function onAfterWrite()
    {
        $group = $this->getOwner();

        if (!$group->UseOwnFileFolder || $group->OwnFileFolderID) {
            return;
        }

        $folder = Folder::create();
        $folder->Name = 'restricted-files-' . $group->Code;
        $folder->CanViewType = 'OnlyTheseUsers';
        $folder->CanEditType = 'OnlyTheseUsers';
        $folder->write();
        $folder->ViewerGroups()->add($group);
        $folder->EditorGroups()->add($group);

        $group->OwnFileFolderID = $folder->ID;
        $group->write();
    }
}
