<?php

namespace MVoelkner\RestrictedPageTree\Tests;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;

class RestrictedFileAccessExtensionTest extends SapphireTest
{
    protected static $fixture_file = 'RestrictedFileAccessExtensionTest.yml';

    private function member(string $identifier): Member
    {
        $this->logInAs($identifier);

        return $this->objFromFixture(Member::class, $identifier);
    }

    private function ownFolder(): Folder
    {
        return $this->objFromFixture(Group::class, 'filesgroup')->OwnFileFolder();
    }

    public function testGroupGetsOwnFolderCreatedOnSave(): void
    {
        $group = $this->objFromFixture(Group::class, 'filesgroup');

        $this->assertGreaterThan(0, $group->OwnFileFolderID);

        $folder = $group->OwnFileFolder();
        $this->assertTrue($folder->exists());
        $this->assertSame('restricted-files-files-restricted-group', $folder->Name);
        $this->assertSame('OnlyTheseUsers', $folder->CanViewType);
        $this->assertSame('OnlyTheseUsers', $folder->CanEditType);
        $this->assertContains($group->ID, $folder->ViewerGroups()->column('ID'));
        $this->assertContains($group->ID, $folder->EditorGroups()->column('ID'));
    }

    public function testGroupFolderNotRecreatedOnSubsequentSaves(): void
    {
        $group = $this->objFromFixture(Group::class, 'filesgroup');
        $originalFolderID = $group->OwnFileFolderID;

        $group->Title = 'Renamed Group';
        $group->write();

        $this->assertSame($originalFolderID, $group->OwnFileFolderID);
    }

    public function testRestrictedMemberCanViewAndEditOwnFolder(): void
    {
        $member = $this->member('filesmember');
        $folder = $this->ownFolder();

        $this->assertTrue($folder->canView($member));
        $this->assertTrue($folder->canEdit($member));
    }

    public function testRestrictedMemberDeniedOutsideOwnFolder(): void
    {
        $member = $this->member('filesmember');
        $other = $this->objFromFixture(Folder::class, 'otherfolder');

        $this->assertFalse($other->canView($member));
        $this->assertFalse($other->canEdit($member));
        $this->assertFalse($other->canDelete($member));
    }

    public function testRestrictedMemberCanCreateWithinOwnFolder(): void
    {
        $member = $this->member('filesmember');

        $file = File::create();
        $this->assertTrue((bool) $file->canCreate($member, ['Parent' => $this->ownFolder()]));
    }

    public function testRestrictedMemberCannotCreateOutsideOwnFolder(): void
    {
        $member = $this->member('filesmember');
        $other = $this->objFromFixture(Folder::class, 'otherfolder');

        $file = File::create();
        $this->assertFalse($file->canCreate($member, ['Parent' => $other]));
    }

    public function testRestrictedMemberCannotCreateAtRoot(): void
    {
        $member = $this->member('filesmember');

        $file = File::create();
        $this->assertFalse($file->canCreate($member));
    }

    public function testRestrictedMemberCanDeleteOwnFile(): void
    {
        $member = $this->member('filesmember');

        $file = File::create(['Name' => 'photo.jpg', 'ParentID' => $this->ownFolder()->ID]);
        $file->write();

        $this->assertTrue($file->canDelete($member));
    }

    public function testAdminBypassesFileRestriction(): void
    {
        $member = $this->member('adminmember');
        $other = $this->objFromFixture(Folder::class, 'otherfolder');

        $this->assertTrue($other->canView($member));
        $this->assertTrue($other->canEdit($member));
    }

    public function testValidateRejectsDisallowedExtensionInsideOwnFolder(): void
    {
        $this->member('filesmember');

        $file = File::create(['Name' => 'malicious.exe', 'ParentID' => $this->ownFolder()->ID]);

        $this->assertFalse($file->validate()->isValid());
    }

    public function testValidateAllowsSafeExtensionInsideOwnFolder(): void
    {
        $this->member('filesmember');

        $file = File::create(['Name' => 'photo.jpg', 'ParentID' => $this->ownFolder()->ID]);

        $this->assertTrue($file->validate()->isValid());
    }

    public function testValidateAllowsAnyExtensionOutsideRestrictedFolder(): void
    {
        $this->member('filesmember');
        $other = $this->objFromFixture(Folder::class, 'otherfolder');

        // .zip isn't in our SAFE_EXTENSIONS whitelist, but is a globally allowed core
        // extension - this proves our stricter whitelist only applies inside the
        // member's own folder, not site-wide.
        $file = File::create(['Name' => 'archive.zip', 'ParentID' => $other->ID]);

        $this->assertTrue($file->validate()->isValid());
    }

    public function testValidateRejectsDotPrefixedFolderNameInsideOwnFolder(): void
    {
        $this->member('filesmember');

        $subfolder = Folder::create(['Name' => '.hidden', 'ParentID' => $this->ownFolder()->ID]);

        $this->assertFalse($subfolder->validate()->isValid());
    }

    public function testValidateAllowsNormalFolderNameInsideOwnFolder(): void
    {
        $this->member('filesmember');

        $subfolder = Folder::create(['Name' => 'vacation-photos', 'ParentID' => $this->ownFolder()->ID]);

        $this->assertTrue($subfolder->validate()->isValid());
    }
}
