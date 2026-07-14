<?php

namespace MVoelkner\RestrictedPageTree\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;

class RestrictedPageTreeExtensionTest extends SapphireTest
{
    protected static $fixture_file = 'RestrictedPageTreeExtensionTest.yml';

    private function member(string $identifier): Member
    {
        $this->logInAs($identifier);

        return $this->objFromFixture(Member::class, $identifier);
    }

    public function testFlatChildrenReturnsOnlyAssignedPagesForRestrictedMember(): void
    {
        $this->member('restrictedmember');

        $children = SiteTree::singleton()->FlatChildrenForRestrictedGroups();

        $this->assertSame(
            [$this->idFromFixture(SiteTree::class, 'assigned')],
            $children->column('ID')
        );
    }

    public function testFlatChildrenReturnsEmptyForNonRootNodeWhenRestricted(): void
    {
        $this->member('restrictedmember');

        $assigned = $this->objFromFixture(SiteTree::class, 'assigned');

        $this->assertCount(0, $assigned->FlatChildrenForRestrictedGroups());
    }

    public function testFlatChildrenReturnsNormalHierarchyForUnrestrictedMember(): void
    {
        $this->member('plainmember');

        $ids = SiteTree::singleton()->FlatChildrenForRestrictedGroups()->column('ID');

        $this->assertContains($this->idFromFixture(SiteTree::class, 'other'), $ids);
        $this->assertContains($this->idFromFixture(SiteTree::class, 'assigned'), $ids);
    }

    public function testRestrictedMemberWithAdminPermissionIsNotRestricted(): void
    {
        $this->member('restrictedadminmember');

        $ids = SiteTree::singleton()->FlatChildrenForRestrictedGroups()->column('ID');

        $this->assertContains($this->idFromFixture(SiteTree::class, 'other'), $ids);
    }

    public function testCanCreateDeniedForRestrictedMember(): void
    {
        $member = $this->member('restrictedmember');
        $assigned = $this->objFromFixture(SiteTree::class, 'assigned');

        $this->assertFalse($assigned->canCreate($member));
    }

    public function testCanDeleteDeniedForRestrictedMember(): void
    {
        $member = $this->member('restrictedmember');
        $assigned = $this->objFromFixture(SiteTree::class, 'assigned');

        $this->assertFalse($assigned->canDelete($member));
    }

    public function testCanDeleteNotForciblyDeniedForAdmin(): void
    {
        $member = $this->member('adminmember');
        $other = $this->objFromFixture(SiteTree::class, 'other');

        $this->assertTrue($other->canDelete($member));
    }
}
