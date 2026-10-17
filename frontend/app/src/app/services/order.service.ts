import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Subject, tap } from 'rxjs';
import { Environment } from '../env';

@Injectable({
  providedIn: 'root'
})
export class OrderService {
  // private url = location.origin + '/wp/wp-json/24os-mail-sy/v1/order/';

  // route for localhost
  // private url = location.origin + '/wordpress/wp-json/24os-mail-sy/v1/order/';

  private url = `${Environment.prodApiEndpointUrl}/order/`;
  // route for example.com
  // private url = location.origin + '/wp-json/24os-mail-sy/v1/order/';


  // private url = 'http://localhost/wordpress/wp-json/24os-mail-sy/v1/order/';

  constructor(private httpClient: HttpClient) {;
  }


  sendOrder(inputData: any){
    return this.httpClient.post(this.url, inputData);
  }


}
