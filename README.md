# Professionals Choice

Monorepo for the Professionals Choice WordPress site.

## Theme

The `commercebuild-velocity` theme is mirrored on the orphan branch
[`commercebuild-velocity`](../../tree/commercebuild-velocity). Each push there
publishes a release zip named `commercebuild-velocity.<version>.zip`.

`blueprint.json` installs the theme straight from the release zip URL, which
pins the version.

## Content

Pages, block content, custom CSS, global styles and the media they use live on
the orphan branch [`bundle`](../../tree/bundle), which the blueprint imports.

Keep developing in Studio, then save the content into the bundle worktree:

```bash
git worktree add ../profchoice-bundle bundle   # once
studio wp bundle export
git -C ../profchoice-bundle add -A
git -C ../profchoice-bundle commit -m "Update content"
git -C ../profchoice-bundle push
```

The command is registered through `wp-cli.yml` from `bin/bundle-command.php`.

## Playground

https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/gtavarescrimsonagility/profchoice/main/blueprint.json

Or locally:

```bash
npx @wp-playground/cli server --blueprint=./blueprint.json
```

Both only work while the repository is public: release assets of a private
repository cannot be downloaded without authentication.
