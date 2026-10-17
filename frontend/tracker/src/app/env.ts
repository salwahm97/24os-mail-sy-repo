/* import { isDevMode } from "@angular/core";
const host = isDevMode() ? "http://localhost/wordpress" : "https://example.com";
export const Environment = {
  apiEndpointUrl: `${host}/wp-json/24os-mail-sy/v1`,
};
 */

export const Environment = {
  devApiEndpointUrl: `http://localhost/wordpress/wp-json/24os-mail-sy/v1`,
  prodApiEndpointUrl: `https://example.com/wp-json/24os-mail-sy/v1`,
};
