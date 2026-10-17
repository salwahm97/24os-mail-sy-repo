# Angular source projects

Three independent Angular projects (each has its own `package.json`):

| Folder | Angular | Purpose | Build output goes to |
|--------|---------|---------|----------------------|
| `app/` | 15 | public order form | `plugin/24os-mail-sy/app/` (built **without** hashing) |
| `orders/` | 18 | staff dashboard (WP admin) | `plugin/24os-mail-sy/orders/` (hashed file names) |
| `tracker/` | 18 | order tracking | `plugin/24os-mail-sy/tracker/` |

Use Node 18.19+ (or 20) for `orders`/`tracker`; Node 16/18 for `app`. Never commit `node_modules/`, `.angular/` or `dist/`.

```bash
cd frontend/<project>
npm install
npx ng build --configuration production
```

The API URL lives in `src/app/env.ts` (`prodApiEndpointUrl`). It is a **placeholder** (`https://example.com/wp-json/24os-mail-sy/v1`):
the plugin's `api-rewrite.js` redirects every call to that URL (and to the dev URL `http://localhost/wordpress/...`) to the REST API of the
WordPress site where the plugin is installed. Keep the placeholder as it is, or, if you change it, add the same value to the `BASES` list in
`plugin/24os-mail-sy/api-rewrite.js`.

See the main README for the post-build steps of each project.
