<?php

namespace MVoelkner\RestrictedPageTree;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Group;

/**
 * @extends Extension<Group>
 */
class GroupPageRestrictionExtension extends Extension
{
    private static $db = [
        'OnlyShowAssignedPages' => 'Boolean',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Permissions',
            CheckboxField::create(
                'OnlyShowAssignedPages',
                'Only show assigned pages (flat list instead of page tree)'
            )->setDescription(
                'Members of this group will only see pages in the "Pages" section where this '
                . 'group is set as a viewer or editor under "Permissions" - regardless of where '
                . 'those pages sit in the site tree. Creating, deleting and reorganising pages is '
                . 'disabled for this group. Does not apply to members with full administrative rights.'
            )
        );
    }
}
