import { Component, Inject } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { TranslateService } from '@ngx-translate/core';
import { NgxImageCompressService } from 'ngx-image-compress';
import { StatusService } from 'src/app/services/status.service';

@Component({
  selector: 'app-order-status-dialog',
  templateUrl: './order-status-dialog.component.html',
  styleUrls: ['./order-status-dialog.component.css']
})
export class OrderStatusDialogComponent {
  show_remove_icon: boolean = false;
  screen_loader: boolean = false;

  statusForm = new FormGroup({
    status: new FormControl('', [ Validators.required ]),
    notes: new FormControl(''),
    image_name: new FormControl(''),
    image_src: new FormControl(''),
    image_url_path: new FormControl(''),
    send: new FormControl(true)
  });

  responseData: any;
  statusesArray: any;

  constructor(
    private imageCompress: NgxImageCompressService,
    private statusService: StatusService,
    private translate: TranslateService,
    private dialogRef:MatDialogRef<OrderStatusDialogComponent>,
    @Inject(MAT_DIALOG_DATA) public data: any
  ){
    translate.addLangs(["en", "de", "ar"]);

  }

  ngOnInit(): void {
    this.translate.use(this.data.adminLang);
    this.statusesArray = this.data.statuses;
    console.log(this.data);

  }

  getExactSize(_size: any) {
    var fSExt = new Array("Bytes", "KB", "MB"),
      i = 0;
    //check if file is in GB,MB,KB or Bytes
    while (_size > 900) {
      _size /= 1024; //divide file size
      i++;
    }
    //get exact size
    var exactSize = Math.round(_size * 100) / 100;
    var typeSize = fSExt[i];

    return [exactSize, typeSize];
  }

  selectFileImage(){
    this.imageCompress.uploadFile().then(({ image, orientation, fileName }) => {
      this.statusForm.controls.image_name.setValue(fileName);

      let type_img = image.substring(image.indexOf("data:image/") + 11, image.lastIndexOf(";"));
      if (type_img == "png" || type_img == "jpeg" || type_img == "jpg") {
        var size_file = this.getExactSize(this.imageCompress.byteCount(image));
        console.log("Size in bytes of the uploaded image was:", size_file[0] + " " + size_file[1]);

        if (size_file[0] > "2.00" && size_file[1] == "MB") {
          this.imageCompress
            .compressFile(image, orientation, 50, 50) // 50% ratio, 50% quality
            .then((compressedImage) => {
              this.statusForm.controls.image_src.setValue(compressedImage);

              size_file = this.getExactSize(this.imageCompress.byteCount(compressedImage));
              console.log("Size in bytes after compression is now:", size_file[0] + " " + size_file[1]);
            });
        } else {
          console.log("Size in bytes is now:", size_file[0] + " " + size_file[1]);
          this.statusForm.controls.image_src.setValue(image);
        }
        this.show_remove_icon = true;

      } else {
        this.statusForm.controls.image_name.markAsTouched();
        this.statusForm.controls.image_name.setErrors({ typeImageInvalid: true });
      }
    });
  }

  removeImage(){
    this.statusForm.controls.image_name.setValue("");
    this.statusForm.controls.image_src.setValue("");
    this.statusForm.controls.image_url_path.setValue("");
    this.show_remove_icon = false;
  }

  submit(order_id:any, order_email:any, order_lang:any, order_no:any){
    if(this.statusForm.valid){
      console.log(this.statusForm.getRawValue());
      this.screen_loader = true;
      this.statusService.addStatusToOrder(order_id, {
        "order_email": order_email,
        "order_lang": order_lang,
        "order_no": order_no,
        "status_form":this.statusForm.getRawValue()
      }).subscribe(res => {
        let responseData: any = res;
        this.screen_loader = false;
        if(responseData['st'] == true){
          this.dialogRef.close(responseData['message']);
        }

      });
    }
  }


}
