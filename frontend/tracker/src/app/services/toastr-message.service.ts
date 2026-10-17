import { Injectable } from '@angular/core';
import { IndividualConfig, ToastrService } from 'ngx-toastr';


export interface toastrPayload{
  message: string;
  title: string;
}

@Injectable({
  providedIn: 'root'
})
export class ToastrMessageService {

  constructor(private toastr: ToastrService) {
    // Define toastr GlobalConfig
    this.toastr.toastrConfig.enableHtml = true;
  }

  closeToast(){
    this.toastr.clear();
  }

  showErrorToast(toast: toastrPayload){
    this.toastr.error(
      toast.message,
      toast.title,
    );
  }

}
