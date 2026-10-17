import { Injectable } from '@angular/core';
import { Environment } from '../env';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class OrderService {

  private url = `${Environment.prodApiEndpointUrl}/order/`;

  constructor(private httpClient: HttpClient) {;
  }

  getOrders() {
    return this.httpClient.get(this.url);
  }

  getOrderById(id: any) {
    return this.httpClient.get(this.url + id);
  }

}
