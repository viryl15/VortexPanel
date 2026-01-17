# Dev: linking packages into the demo app

The demo app uses Composer `path` repositories:

- `../../packages/vortexpanel`
- `../../packages/vortexpanel-core`
- `../../packages/vortexpanel-ui`

This allows hot iteration on package code without publishing.

If you change package `composer.json`, run:

```bash
cd apps/demo
composer update
```
