# silverstripe-restricted-page-tree

Two independent building blocks for locking a CMS group down to a small, self-contained
slice of the site:

- **Pages**: a flat, permission-scoped view of the "Pages" section - members only see the
  pages explicitly assigned to their group, regardless of where those pages sit in the site
  tree. No unassigned pages, no parent hierarchy, no create/delete.
- **Files**: a dedicated, auto-created folder per group - members can upload, edit and
  delete files (and create subfolders) only inside their own folder, restricted to safe
  file types.

Requires SilverStripe CMS 6.

## Installation

Add this repo as a path (or VCS) repository and require it:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../silverstripe-restricted-page-tree"
        }
    ],
    "require": {
        "mvoelkner/silverstripe-restricted-page-tree": "*"
    }
}
```

For a remote/non-local checkout, use a VCS repository (git URL) instead of `path`.

Then:

```bash
composer update mvoelkner/silverstripe-restricted-page-tree
vendor/bin/sake dev/build flush=all
```

## Usage: restricted Pages

1. Open the CMS group that should be restricted (Security → Groups). On the "Permissions"
   tab, tick **"Only show assigned pages (flat list instead of page tree)"**.
2. On each page this group should have access to, go to Settings → Permissions and set
   both "Who can view this page" and "Who can edit this page" to "Only these people",
   then add the group.

Members of the group will then see only those pages in the CMS, flat, with no ability to
create, delete or reorganise pages. Pages can be at any depth in the site tree and are
shown regardless of the group's access to their ancestors.

Members with `ADMIN` or `SITETREE_EDIT_ALL` permissions are never restricted, even if they
also belong to a flagged group.

### Known limitation

If a page assigned to a restricted group has real child pages that are *not* themselves
assigned to the group, its tree node may show an expand arrow that reveals nothing when
clicked (cosmetic only - no unassigned content is ever exposed).

## Usage: restricted Files folder

1. Open the CMS group that should be restricted (Security → Groups). On the "Permissions"
   tab, tick **"Use own restricted file folder"**, then save the group.
2. A folder named `restricted-files-<group-code>` is created automatically in the "Files"
   section and locked to this group.
3. Make sure the group also has the **"Access to 'Files' section"** permission
   (`CMS_ACCESS_AssetAdmin`) - this is not granted automatically.

Members of the group can then upload, edit and delete files in their own folder (and any
subfolders they create there), but cannot view, edit or create anything outside it. Only
common image and document file types are allowed inside the folder (jpg, jpeg, png, gif,
webp, pdf, doc, docx, xls, xlsx, ppt, pptx, odt, ods, odp, txt, csv), and subfolder names
starting with "." are rejected.

Members with `ADMIN` or `File::EDIT_ALL` permissions are never restricted, even if they
also belong to a flagged group.

### Known limitation

The "Files" section's folder listing always shows every folder at the root level (with a
generic "Restricted access" badge on locked ones) - unlike the Pages tree, there's no
supported extension point to hide folders a member can't access from that list. Opening,
uploading to or editing anything outside the member's own folder is still correctly denied.
