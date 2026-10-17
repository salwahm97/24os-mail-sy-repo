import { Injectable } from '@angular/core';
import { Environment } from '../env';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class LangService {

  private url = `${Environment.prodApiEndpointUrl}/lang/`;

  constructor(private httpClient: HttpClient) {;
  }

  getLang(){
    return this.httpClient.get(this.url);
  }

}
