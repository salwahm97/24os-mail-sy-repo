import { Injectable } from '@angular/core';
import { Environment } from '../env';
import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { catchError, throwError } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class TrackService {

  private url = `${Environment.prodApiEndpointUrl}/track/`;

  constructor(private httpClient: HttpClient) {;
  }

  trackOrderStatuses(code: any, emailOrFirstname: any, conntrolIsEmail: boolean) {
    let params = new HttpParams().set("email", emailOrFirstname).set("controlIsEmail", conntrolIsEmail);
    return this.httpClient.get(this.url + code, {params: params}).pipe(
      catchError(this.handleError)
    );
  }

  private handleError(error: HttpErrorResponse) {
    if (error.error instanceof ErrorEvent) {
      // A client-side or network error occurred. Handle it accordingly.
      console.error('An error occurred:', error.error.message);
    } else {
      // The backend returned an unsuccessful response code.
      // The response body may contain clues as to what went wrong,
      console.error(
        `Backend returned code ${error.status}, ` +
        `body was: ${error.error}`);
    }
    // return an observable with a user-facing error message
    return throwError(
      'Something bad happened; please try again later.');
  };
}
