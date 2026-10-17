import { HttpClient } from "@angular/common/http";
import { Injectable } from "@angular/core";
import { Environment } from "../env";

@Injectable({
  providedIn: "root",
})
export class ItemService {
  // private url = location.origin + '/wp/wp-json/24os-mail-sy/v1/items/';
  // private url2 = location.origin + '/wp/wp-json/24os-mail-sy/v1/category/';
  // private url3 = location.origin + '/wp/wp-json/24os-mail-sy/v1/type-items/';

  // route for localhost
  /*   private url = location.origin + "/wordpress/wp-json/24os-mail-sy/v1/items/";
  private url2 = location.origin + "/wordpress/wp-json/24os-mail-sy/v1/category/";
  private url3 = location.origin + "/wordpress/wp-json/24os-mail-sy/v1/type-items/"; */

  // route for example.com
  // private url = location.origin + '/wp-json/24os-mail-sy/v1/items/';
  // private url2 = location.origin + '/wp-json/24os-mail-sy/v1/category/';
  // private url3 = location.origin + '/wp-json/24os-mail-sy/v1/type-items/';

  private url = `${Environment.prodApiEndpointUrl}/items/`;
  private url2 = `${Environment.prodApiEndpointUrl}/category/`;
  private url3 = `${Environment.prodApiEndpointUrl}/type-items/`;

  // private url  = 'http://localhost/wordpress/wp-json/24os-mail-sy/v1/items/';
  // private url2 = 'http://localhost/wordpress/wp-json/24os-mail-sy/v1/category/';
  // private url3 = 'http://localhost/wordpress/wp-json/24os-mail-sy/v1/type-items/';

  constructor(private httpClient: HttpClient) {console.log(this.url)}

  getItems(lang: string) {
    return this.httpClient.get(this.url + lang);
  }

  getCategories(lang: string) {
    return this.httpClient.get(this.url2 + lang);
  }

  getSelectedItems(items: any, cats: any, sheet_number: number, lang: string) {
    return this.httpClient.post(this.url, { items: items, cats: cats, lang: lang, sheet_number: sheet_number });
  }

  getTypeSelectedItems(items: any, lang: string) {
    return this.httpClient.post(this.url3, { items: items, lang: lang });
  }

  getSelectedCats(cat: any, lang: string) {
    return this.httpClient.post(this.url2, { cat: cat, lang: lang });
  }
}
