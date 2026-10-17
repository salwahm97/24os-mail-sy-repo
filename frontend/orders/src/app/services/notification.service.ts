import { Injectable } from '@angular/core';
import { IndividualConfig, ToastrService } from 'ngx-toastr';

export interface toastrPayload{
  message: string;
  title: string;
  ic: IndividualConfig;
  type:string;
}

@Injectable({
  providedIn: 'root'
})
export class NotificationService {

  constructor(private toastr: ToastrService) {
    // Define toastr GlobalConfig
    this.toastr.toastrConfig.enableHtml = true;
  }

  showToast(toast: toastrPayload){
    this.toastr.show(
      toast.message,
      toast.title,
      toast.ic,
      'toast-'+ toast.type
    );
  }

}
