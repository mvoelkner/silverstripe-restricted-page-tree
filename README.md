# silverstripe-restricted-page-tree

Gives a CMS group a flat, permission-scoped view of the "Pages" section: members only see
the pages explicitly assigned to their group, regardless of where those pages sit in the
site tree. No unassigned pages, no parent hierarchy, no create/delete.

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

## Usage

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

## Known limitation

If a page assigned to a restricted group has real child pages that are *not* themselves
assigned to the group, its tree node may show an expand arrow that reveals nothing when
clicked (cosmetic only - no unassigned content is ever exposed).
