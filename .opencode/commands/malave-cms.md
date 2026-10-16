---
description: Deploy malave.com and verify the live site
---

Deploy the malave.com site and verify it end to end. Work from `~/modern-redesign`.

1. Confirm the working tree is clean and everything intended is committed.
2. Deploy: `gh workflow run deploy.yml --ref master`, then
   `gh run watch <run-id> --exit-status` and report the conclusion.
3. Verify live over HTTPS:
   - home returns 200 with the expected `<title>`
   - `http://malave.com/` responds 301 to HTTPS
   - `/library/` and `/images/uploads/.htaccess` return 403
   - anonymous `/admin/` redirects (302) to the login page
4. Sweep every local asset referenced by the page and report anything that is not 200.
5. Report the run result and any regression. Do not change site content.

Context for this run: $ARGUMENTS
