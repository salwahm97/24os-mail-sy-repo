import { Injectable } from '@angular/core';
import { Environment } from '../env';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class StatusService {

  private url = `${Environment.prodApiEndpointUrl}/status`;

  constructor(private httpClient: HttpClient) {
  }

  getOrderStatuses(order_id: any) {
    return this.httpClient.get(this.url + "/" + order_id);
  }

  getStatuses() {
    return this.httpClient.get(this.url);
  }

  addStatusToOrder(order_id: any, inputData: any){
    return this.httpClient.post(this.url + "/" + order_id, inputData);
  }
}
