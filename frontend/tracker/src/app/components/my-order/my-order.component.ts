import { Component, ElementRef, ViewChild } from '@angular/core';

import { FormControl, FormGroup, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatCardModule } from '@angular/material/card';
import { MatStepper, MatStepperModule } from '@angular/material/stepper';
import { TranslateModule, TranslateService } from '@ngx-translate/core';
import { ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { TrackService } from '../../services/track.service';
import { MatProgressSpinner } from '@angular/material/progress-spinner';
import { orderCodeValidator } from '../../validators/order-code.validator';
import { ToastrMessageService, toastrPayload } from '../../services/toastr-message.service';


@Component({
  selector: 'app-my-order',
  standalone: true,
  imports: [
    CommonModule,
    MatFormFieldModule,
    MatInputModule, 
    FormsModule, 
    ReactiveFormsModule,
    MatButtonModule,
    MatIconModule,
    MatCardModule,
    MatStepperModule,
    MatProgressSpinner,
    TranslateModule
  ],
  templateUrl: './my-order.component.html',
  styleUrl: './my-order.component.css'
})
export class MyOrderComponent {

  lang: any;
  dir:any = "ltr";
  screen_loader: boolean = false;

  form = new FormGroup({
    email_firstname: new FormControl('', [ Validators.required ]),
    order_code: new FormControl('', [ Validators.required, orderCodeValidator ])
  });

  
  @ViewChild('stepper')  stepper !: MatStepper;
  selectedStepNumber: any = 0;
  statusArr: any[] = [];

  order_code_param: any;
  showOrderStatus: boolean = false;
  noOrderStatus: boolean = false;
  controlIsEmail: boolean = true;
  
  toast!: toastrPayload;

  constructor(
    private translate: TranslateService,
    private route: ActivatedRoute,
    private trackService: TrackService,
    private cs: ToastrMessageService,
    private hostElement: ElementRef
  ) {
    translate.addLangs(["en", "de", "ar"]);
  
  }

  ngOnInit() {

    this.route.queryParams.subscribe((params) => {
      this.lang = params["lang"];
      this.translate.setDefaultLang('en');
      if (this.lang != undefined && ["en", "de", "ar"].indexOf(this.lang) !== -1) {
        this.translate.use(this.lang);
        if(this.lang == "ar"){
          this.dir = "rtl";
        }
      }

      this.order_code_param = params["order_code"];
      if (this.order_code_param != undefined && this.order_code_param != ""){
       
        this.form.controls.order_code.setValue(this.order_code_param);
      }
    });
  }

  // ngAfterContentInit(): void {
  //   this.sendScrollHeight();
  // }

  // sendScrollHeight() {
  //   setTimeout(() => {
  //     const scrollHeight = this.hostElement.nativeElement.scrollHeight;
  //     window.parent.postMessage({ scrollHeight }, "*");
  //     console.log("scrollHeight: " + scrollHeight);
  //   }, 300);
  // }

  

  checkEmail(){
    if(this.form.controls.email_firstname.value?.includes("@")){
      this.form.controls.email_firstname.addValidators(Validators.email);
      this.form.controls.email_firstname.updateValueAndValidity();
      this.controlIsEmail = true;
    }
    else{
      this.form.controls.email_firstname.clearValidators();
      this.form.controls.email_firstname.setValidators(Validators.required);
      this.form.controls.email_firstname.updateValueAndValidity();
      this.controlIsEmail = false;
    }
  }

  getOrderStatus(order_code: any){
    this.cs.closeToast();
    this.showOrderStatus = false;
    this.noOrderStatus = false;
    if(this.form.valid){
      console.log(order_code);
      console.log(this.form.controls.email_firstname.value);
      this.screen_loader = true;
      this.trackService.trackOrderStatuses(order_code, this.form.controls.email_firstname.value, this.controlIsEmail).subscribe((response) => {
        let responseStatusData: any = response;
        this.screen_loader = false;
        if(responseStatusData["statuses"]){
          this.statusArr = responseStatusData["statuses"];
          console.log(responseStatusData);

          if(this.statusArr.length > 0){
            this.showOrderStatus = true;
            this.selectedStepNumber = this.statusArr.length;
            setTimeout(() => {
              for (let i = 0; i < this.selectedStepNumber; i++) {
                this.stepper.next();
              }
            }, 10);
          }
          else{
            this.noOrderStatus = true;
          }
        }
        else{
          let status = "Error";
          let msg = responseStatusData['error'];
          this.translate.get(status).subscribe(t=>{status = t;});
          this.translate.get(msg).subscribe(t=>{msg = t;});
          this.toastShowError(status, msg);
        }
        
      });
      
    }
  
  }

  toastShowError(title: string, message:string){
    this.toast = {
      message: message,
      title: title
    };
    this.cs.showErrorToast(this.toast);
  }
  
}

