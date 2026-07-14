<?php

namespace MVoelkner\RestrictedPageTree;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\SS_List;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Gives CMS groups flagged with Group::OnlyShowAssignedPages a flat, permission-scoped
 * view of the "Pages" section: only the pages explicitly assigned to one of their groups
 * (via the standard "Only these people" view/edit permissions) are shown, regardless of
 * where those pages sit in the site tree. Creating and deleting pages is disabled for
 * these members (reorganising is already blocked as long as the group lacks the
 * SITETREE_REORGANISE permission). Members with full site-wide access (ADMIN or
 * SITETREE_EDIT_ALL) are never restricted, even if they also belong to a flagged group.
 *
 * @extends Extension<SiteTree>
 */
class RestrictedPageTreeExtension extends Extension
{
    /**
     * Registered as SiteTree.tree_children_method so it's used everywhere the CMS
     * builds the page tree.
     */
    public function FlatChildrenForRestrictedGroups(): SS_List
    {
        $owner = $this->getOwner();
        $member = Security::getCurrentUser();

        if (!$this->isRestrictedMember($member)) {
            return $owner->getChildrenForTree();
        }

        // Root node: list the pages assigned to this member's restricted group(s), flat.
        if (!$owner->ID) {
            $groupIDs = $this->getRestrictedGroupIDs($member);

            return SiteTree::get()->filterAny([
                'ViewerGroups.ID' => $groupIDs,
                'EditorGroups.ID' => $groupIDs,
            ])->sort('Title');
        }

        // Any other node: restricted members never see nested pages.
        return ArrayList::create();
    }

    public function canCreate($member = null, $context = [])
    {
        return $this->isRestrictedMember($member) ? false : null;
    }

    public function canDelete($member = null)
    {
        return $this->isRestrictedMember($member) ? false : null;
    }

    private function isRestrictedMember(?Member $member): bool
    {
        if (!$member) {
            return false;
        }

        if (Permission::checkMember($member, ['ADMIN', 'SITETREE_EDIT_ALL'])) {
            return false;
        }

        return $this->getRestrictedGroupIDs($member) !== [];
    }

    private function getRestrictedGroupIDs(Member $member): array
    {
        return $member->Groups()->filter('OnlyShowAssignedPages', true)->column('ID');
    }
}
