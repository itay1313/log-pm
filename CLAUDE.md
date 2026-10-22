# log-pm: working rules

## Git: every session ends in a PR

The repo is https://github.com/itay1313/log-pm. `main` is the restore point Itay goes back to, so nothing lands on it except through a merged PR.

**Start of session**
1. `git fetch origin` and `git status`. Uncommitted changes you didn't make: stop and ask.
2. `gh pr list --state open`. If an earlier session's PR is still open, ask whether to merge it first or build on top of its branch.
3. Branch from an up-to-date main: `git switch main && git pull --ff-only && git switch -c <type>/<short-name>`.
   Types: `feat/`, `fix/`, `design/`, `chore/`, `docs/`. Name the work, not the date: `feat/projects-map-filters`.

**During**
- Commit each logical step on its own, with a message that says what changed and why. Never commit to `main` directly.
- Never commit secrets: passwords stay in the Keychain, API tokens in the WordPress settings page.

**End of session**
1. Push the branch and open a PR into `main` with `gh pr create`. The body says what changed, how it was checked (Playground, uPress, tests), and anything left open.
2. Don't merge without Itay's OK. Offer the merge in the wrap-up. Merge with **squash**, so each session is one commit on `main` that can be reverted on its own.
3. After the merge: `git switch main && git pull --ff-only` and delete the branch.

**Going back**
- Undo a whole session: the "Revert" button on the merged PR in GitHub, or `git revert <squash-sha>` on a new branch, then a PR.
- Never `git push --force` to `main`, never `git reset --hard` on shared history.

## Deploy

Merging a PR is deploying it. Every push to `main` runs `.github/workflows/deploy.yml`, which builds the theme, zips the theme and log-core, and POSTs the zip over HTTPS to `/wp-json/log/v1/deploy` on uPress (secret `DEPLOY_URL`). log-core (`inc/deploy.php`) accepts it only with a GitHub OIDC token from this repo's deploy.yml, bound to the zip's hash, then swaps both folders in. The version it replaced stays in `wp-content/upgrade/log-deploy/previous/`.

- After a merge, check the run: `gh run list --workflow deploy.yml --limit 1`, then `gh run watch`.
- To test a branch on uPress before merging: `gh workflow run deploy.yml --ref <branch>`, and say so in the PR. The next merge to `main` overwrites it.
- FTP doesn't work from GitHub: uPress only lets approved IPs through on port 21. `tools/deploy.sh` (FTP, host and user from `.env`, password from the Keychain) works from this Mac only while its IP is approved in the uPress panel. Use it only when Actions is down, or if a broken log-core can't receive deploys anymore.
- Renaming the repo or moving `deploy.yml` breaks deploys: the repo name, its id and the workflow path are pinned in `inc/deploy.php`.
- The repo is public. Client documents (`docs/*.pdf`) stay local and ignored; never commit FTP details, keys or quotes.
