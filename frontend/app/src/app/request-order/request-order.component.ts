import { BreakpointObserver } from "@angular/cdk/layout";
import { STEPPER_GLOBAL_OPTIONS } from "@angular/cdk/stepper";
import { AfterContentInit, Component, ElementRef, OnInit } from "@angular/core";
import { FormBuilder, Validators } from "@angular/forms";
import { MatSnackBar } from "@angular/material/snack-bar";
import { MatStepper, StepperOrientation } from "@angular/material/stepper";
import { ActivatedRoute } from "@angular/router";
import { TranslateService } from "@ngx-translate/core";
import { NgxImageCompressService } from "ngx-image-compress";
import { Observable } from "rxjs";
import { map } from "rxjs/operators";
import { ItemService } from "../services/item.service";
import { OrderService } from "../services/order.service";
import { SnackbarComponent } from "../snackbar/snackbar.component";

@Component({
  selector: "app-request-order",
  templateUrl: "./request-order.component.html",
  styleUrls: ["./request-order.component.css"],
  providers: [
    {
      provide: STEPPER_GLOBAL_OPTIONS,
      useValue: { displayDefaultIndicatorType: false },
    },
  ],
})
export class RequestOrderComponent implements OnInit, AfterContentInit {
  displayedColumns: string[] = ["item_name", "item_duration", "item_price", "item_desc"];
  dataSource: any[] = [];
  screen_loader!: boolean;
  personalInfo: any;
  mailInfo: any;
  mailInfoFlag: boolean = false;
  itemsList!: any[];
  categoriesList1!: any[];
  categoriesList2!: any[];

  mailItemsList!: any[];

  indexTab: any = 0;
  responseData: any;
  error_address: boolean = false;
  error_notes: boolean = false;
  firstnameError: boolean = false;
  parentnameError: boolean = false;
  lastnameError: boolean = false;
  rec_firstnameError: boolean = false;
  rec_parentnameError: boolean = false;
  rec_lastnameError: boolean = false;
  phoneError: boolean = false;
  rec_phoneError: boolean = false;
  id_numberError: boolean = false;
  emailError: boolean = false;
  preview1Error: boolean = false;
  preview2Error: boolean = false;
  cityError: boolean = false;
  note_mandatory: boolean = false;
  flag: boolean = true;

  direction_css!: string;
  stepperOrientation: Observable<StepperOrientation>;
  cats: any[] = [];
  total_price: any;
  total_duration: any;
  lang: any;

  disable_reorder_btn: boolean = true;
  notes: any;
  start_stepper: boolean = false;
  start_mailFormGroup!: boolean;
  id_number: any;
  sheet_number!: number;

  preview1_compress: string = "";
  preview2_compress: string = "";

  show_msg_cat1: boolean = false;
  show_msg_cat2: boolean = false;
  show_langs_cat2: boolean = false;

  languages: any[] = [ "English", "German", "French", "Italian", "Other"];

  constructor(
    private _formBuilder: FormBuilder,
    private itemService: ItemService,
    private orderService: OrderService,
    private translate: TranslateService,
    breakpointObserver: BreakpointObserver,
    private _snackBar: MatSnackBar,
    private route: ActivatedRoute,
    private imageCompress: NgxImageCompressService,
    private hostElement: ElementRef
  ) {
    translate.addLangs(["en", "de", "ar"]);

    this.stepperOrientation = breakpointObserver.observe("(min-width: 800px)").pipe(map(({ matches }) => (matches ? "horizontal" : "vertical")));
  }

  personalInfoFormGroup = this._formBuilder.group({
    firstname: ["", Validators.required],
    lastname: ["", Validators.required],
    address: ["", Validators.required],
    phone: ["", Validators.required],
    email: [""],
  });

  officialDocsFormGroup1 = this._formBuilder.group({
    items: [[], Validators.required],
    categories1: [""],
    categories2: [""],
    notes: [""],
    sheet_number: ["", Validators.required],
    translate_language: [""]
  });
  officialDocsFormGroup2 = this._formBuilder.group({
    id_number: [""]
  });

  mailFormGroup1 = this._formBuilder.group({
    mail_type: ["", Validators.required]
  });
  mailFormGroup2 = this._formBuilder.group({
    rec_firstname: ["", Validators.required],
    rec_parentname: ["", Validators.required],
    rec_lastname: ["", Validators.required],
    rec_address: ["", Validators.required],
    city: ["", Validators.required],
    rec_phone: ["", Validators.required],
  });

  ngOnInit() {
    this.route.queryParams.subscribe((params) => {
      this.lang = params["lang"];
      if (this.lang != undefined) {
        this.translate.setDefaultLang(this.lang);
        this.translate.use(this.lang);

        if (this.lang == "ar") {
          this.direction_css = "direction: rtl;";
        } else if (this.lang == "en") {
          this.direction_css = "direction: unset;";
        } else if (this.lang == "de") {
          this.direction_css = "direction: unset;";
        }

        this.setup_secound_step(this.lang);
      }
    });

    this.personalInfo = {
      email: "",
      firstname: "",
      lastname: "",
      phone: "",
      address: "",
    };

    this.mailFormGroup1.controls["mail_type"].setValue("normal");

  }

  ngAfterContentInit(): void {
    this.sendScrollHeight();
  }

  sendScrollHeight() {
    setTimeout(() => {
      const scrollHeight = this.hostElement.nativeElement.scrollHeight;
      window.parent.postMessage({ scrollHeight }, "*");
      console.log("scrollHeight: " + scrollHeight);
    }, 300);
  }

  setup_secound_step(lang: any) {
    this.itemsList = [];
    this.categoriesList1 = [];
    this.categoriesList2 = [];

    this.mailItemsList = [];
    this.screen_loader = true;
    this.itemService.getItems(lang).subscribe((response) => {
      this.responseData = response;
      this.screen_loader = false;
      for (let index = 0; index < this.responseData.officialDocsItems.length; index++) {
        const element = this.responseData.officialDocsItems[index];
        this.itemsList.push({ id: element.id, item_name: element.item_name });
      }

      for (let index = 0; index < this.responseData.mailItems.length; index++) {
        const element = this.responseData.mailItems[index];
        this.mailItemsList.push({
          id: element.id,
          item_name: element.item_name,
          item_price_total: element.item_price_total,
          item_duration: element.item_duration,
        });
      }
      if (this.mailItemsList.length > 0) {
        this.mailFormGroup1.controls["mail_type"].setValue(this.mailItemsList[0].id);
      }

    });

    this.itemService.getCategories(lang).subscribe((response) => {
      this.responseData = response;
      let categories_group_1 = this.responseData.categories_group_1;
      let categories_group_2 = this.responseData.categories_group_2;
      this.screen_loader = false;
      for (let index = 0; index < categories_group_1.length; index++) {
        const element = categories_group_1[index];
        this.categoriesList1.push({ id: element.id, category_name: element.category_name });
      }
      if (this.categoriesList1.length > 0) {
        this.officialDocsFormGroup1.controls["categories1"].setValue(this.categoriesList1[0].id);
      }

      for (let index = 0; index < categories_group_2.length; index++) {
        const element = categories_group_2[index];
        this.categoriesList2.push({ id: element.id, category_name: element.category_name });
      }
      if (this.categoriesList2.length > 0) {
        this.officialDocsFormGroup1.controls["categories2"].setValue(this.categoriesList2[1].id);
      }
    });

    this.officialDocsFormGroup1.controls["sheet_number"].setValue("1");
  }

  start_mail() {
    this.indexTab = 0;
    this.start_stepper = true;
    this.start_mailFormGroup = true;
  }

  start_official_documents() {
    this.indexTab = 1;
    this.start_stepper = true;
    this.start_mailFormGroup = false;
  }

  createOrder(stepper: MatStepper) {
    this.firstnameError = false;
    this.lastnameError = false;
    this.phoneError = false;
    this.emailError = false;

    if (this.personalInfoFormGroup.status == "VALID") {
      let email = this.personalInfoFormGroup.getRawValue().email?.trim().toLowerCase();
      let firstname = this.personalInfoFormGroup.getRawValue().firstname?.trim();
      let lastname = this.personalInfoFormGroup.getRawValue().lastname?.trim();
      let phone = this.personalInfoFormGroup.getRawValue().phone?.replace(/\s/g, '');
      let address = this.personalInfoFormGroup.getRawValue().address?.trim();

      if (firstname != "" && lastname != "" && phone != "" && address != "") {
        if (!/^([a-zA-ZäöüßÄÖÜẞ\u0600-\u06FF' ]+)$/.test(String(firstname))) {
          this.firstnameError = true;
        }
        if (!/^([a-zA-ZäöüßÄÖÜẞ\u0600-\u06FF' ]+)$/.test(String(lastname))) {
          this.lastnameError = true;
        }
        if (!/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,8}$/.test(String(phone))) {
          this.phoneError = true;

          // valid formats:
          // (123) 456-7890
          // (123)456-7890
          // 123-456-7890
          // 123.456.7890
          // 1234567890
          // +31636363634
          // 075-63546725
        }

        if (this.firstnameError == false && this.lastnameError == false && this.phoneError == false) {
          if (email != "") {
            if (/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,10}$/.test(String(email))) {
              // localStorage.setItem('email', String(email));
              // localStorage.setItem('firstname', String(firstname));
              // localStorage.setItem('lastname', String(lastname));
              // localStorage.setItem('phone', String(phone));
              // localStorage.setItem('address', String(address));

              this.personalInfo = {
                email: email,
                firstname: firstname,
                lastname: lastname,
                phone: phone,
                address: address,
              };
              stepper.next();
            } else {
              this.emailError = true;
            }
          } else {
            this.personalInfo = {
              email: "",
              firstname: firstname,
              lastname: lastname,
              phone: phone,
              address: address,
            };
            stepper.next();
          }
        }
      } else {
        this.personalInfoFormGroup = this._formBuilder.group({
          firstname: ["", Validators.required],
          lastname: ["", Validators.required],
          address: ["", Validators.required],
          phone: ["", Validators.required],
          email: [""],
        });
      }
    }
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

  selectFileImage1(event: any): void {
    this.preview1Error = false;
    this.imageCompress.uploadFile().then(({ image, orientation }) => {
      let type_img = image.substring(image.indexOf("data:image/") + 11, image.lastIndexOf(";"));
      if (type_img == "png" || type_img == "jpeg" || type_img == "jpg") {
        var size_file = this.getExactSize(this.imageCompress.byteCount(image));
        console.log("Size in bytes of the uploaded image was:", size_file[0] + " " + size_file[1]);

        if (size_file[0] > "2.00" && size_file[1] == "MB") {
          this.imageCompress
            .compressFile(image, orientation, 50, 50) // 50% ratio, 50% quality
            .then((compressedImage) => {
              this.preview1_compress = compressedImage;
              size_file = this.getExactSize(this.imageCompress.byteCount(compressedImage));
              console.log("Size in bytes after compression is now:", size_file[0] + " " + size_file[1]);
            });
        } else {
          console.log("Size in bytes is now:", size_file[0] + " " + size_file[1]);
          this.preview1_compress = image;
        }
      } else {
        this.preview1Error = true;
      }
    });
  }

  selectFileImage2(): void {
    this.preview2Error = false;
    this.imageCompress.uploadFile().then(({ image, orientation }) => {
      let type_img = image.substring(image.indexOf("data:image/") + 11, image.lastIndexOf(";"));
      if (type_img == "png" || type_img == "jpeg" || type_img == "jpg") {
        var size_file = this.getExactSize(this.imageCompress.byteCount(image));
        console.log("Size in bytes of the uploaded image was:", size_file[0] + " " + size_file[1]);

        if (size_file[0] > "2.00" && size_file[1] == "MB") {
          this.imageCompress
            .compressFile(image, orientation, 50, 50) // 50% ratio, 50% quality
            .then((compressedImage) => {
              this.preview2_compress = compressedImage;
              size_file = this.getExactSize(this.imageCompress.byteCount(compressedImage));
              console.log("Size in bytes after compression is now:", size_file[0] + " " + size_file[1]);
            });
        } else {
          console.log("Size in bytes is now:", size_file[0] + " " + size_file[1]);
          this.preview2_compress = image;
        }
      } else {
        this.preview2Error = true;
      }
    });
  }

  selectionItemChange(selectedItem: any) {
    this.screen_loader = true;
    this.officialDocsFormGroup1.controls.translate_language.setValue("");

    this.itemService.getTypeSelectedItems(selectedItem.value, this.lang).subscribe((response) => {
      this.responseData = response;
      this.screen_loader = false;
      if (this.responseData.message) {
        if (this.responseData.flag) {
          this.flag = true;
          this.officialDocsFormGroup1.controls["categories1"].setValue(this.categoriesList1[0].id);
          this.officialDocsFormGroup1.controls["categories2"].setValue(this.categoriesList2[1].id);
          this.show_langs_cat2 = false;
          this.show_msg_cat1 = false;
          this.show_msg_cat2 = false;
        } else {
          this.flag = false;
          this.officialDocsFormGroup1.controls["categories1"].setValue("");
          this.officialDocsFormGroup1.controls["categories2"].setValue("");
        }
      }
    });
  }

  next(stepper: MatStepper, group_number: any) {
    if (this.indexTab == 1) {
      if(group_number == 1){
        if (this.officialDocsFormGroup1.status == "VALID") {
          this.mailInfoFlag = false;
          let items = this.officialDocsFormGroup1.getRawValue().items;
          let categories1 = this.officialDocsFormGroup1.getRawValue().categories1;
          let categories2 = this.officialDocsFormGroup1.getRawValue().categories2;
          this.notes = this.officialDocsFormGroup1.getRawValue().notes?.trim();
          this.sheet_number = Number(this.officialDocsFormGroup1.getRawValue().sheet_number);
          if(this.officialDocsFormGroup1.getRawValue().translate_language == "" ||
            this.officialDocsFormGroup1.getRawValue().translate_language == "Other" ||
            this.officialDocsFormGroup1.getRawValue().translate_language == "اخر" ||
            this.officialDocsFormGroup1.getRawValue().translate_language == "Andere"){
              this.officialDocsFormGroup1.controls.translate_language.setValue("");
          }

          this.screen_loader = true;
          this.itemService.getSelectedItems(items, [categories1, categories2], this.sheet_number, this.lang).subscribe((response) => {
            this.responseData = response;
            this.screen_loader = false;
            if (this.responseData.message) {
              this.screen_loader = false;
              this.dataSource = this.responseData.selected_items;
              this.cats = this.responseData.selected_cats;
              this.total_price = this.responseData.total_price;
              this.total_duration = this.responseData.total_duration;

              this.dataSource.forEach((ds) => {
                if (
                  ds.item_name == "شهادات ومصدقات" ||
                  ds.item_name == "غير ذلك" ||
                  ds.item_name == "Certificates and Endorsements" ||
                  ds.item_name == "Other" ||
                  ds.item_name == "Zertifikate und Vermerke" ||
                  ds.item_name == "Andere"
                ) {
                  this.note_mandatory = true;
                } else {
                  this.note_mandatory = false;
                }
              });

              if (this.note_mandatory) {
                if (this.notes != "") {
                  stepper.next();
                  this.error_notes = false;
                } else {
                  this.error_notes = true;
                }
              } else {
                this.error_notes = false;
                stepper.next();
              }
            }
          });

        }
      }
      else{
        this.id_numberError = false;
        if (this.officialDocsFormGroup2.status == "VALID") {
          this.mailInfoFlag = false;
          this.id_number = this.officialDocsFormGroup2.getRawValue().id_number?.trim();

          if (this.id_number != "" && !/^\d+$/.test(String(this.id_number))) {
            this.id_numberError = true;
          }
          if (this.id_numberError == false && this.preview1Error == false && this.preview2Error == false) {
            stepper.next();
          }
        }
      }

    } else {
      if(group_number == 1){
        if (this.mailFormGroup1.status == "VALID") {
          let mail_type = this.mailFormGroup1.getRawValue().mail_type;

          this.screen_loader = true;
          this.itemService.getSelectedItems([mail_type], [], 0, this.lang).subscribe((response) => {
            this.responseData = response;
            if (this.responseData.message) {
              this.screen_loader = false;
              this.dataSource = this.responseData.selected_items;
              this.cats = this.responseData.selected_cats;
              this.total_price = this.responseData.total_price;
              this.total_duration = this.responseData.total_duration;

              stepper.next();
            }
          });
        }
      }
      else{
        if (this.mailFormGroup2.status == "VALID") {
          this.cityError = false;
          this.rec_firstnameError = false;
          this.rec_parentnameError = false;
          this.rec_lastnameError = false;
          this.rec_phoneError = false;

          let rec_address = this.mailFormGroup2.getRawValue().rec_address;
          let mail_type = this.mailFormGroup1.getRawValue().mail_type;
          let city = this.mailFormGroup2.getRawValue().city?.trim();
          let rec_firstname = this.mailFormGroup2.getRawValue().rec_firstname?.trim();
          let rec_parentname = this.mailFormGroup2.getRawValue().rec_parentname?.trim();
          let rec_lastname = this.mailFormGroup2.getRawValue().rec_lastname?.trim();
          let rec_phone = this.mailFormGroup2.getRawValue().rec_phone?.replace(/\s/g, '');

          if (rec_firstname != "" && rec_parentname != "" && rec_lastname != "" && rec_phone != "" && rec_address != "" && city != "") {
            if (!/^([a-zA-ZäöüßÄÖÜẞ\u0600-\u06FF' ]+)$/.test(String(rec_firstname))) {
              this.rec_firstnameError = true;
            }
            if (!/^([a-zA-ZäöüßÄÖÜẞ\u0600-\u06FF' ]+)$/.test(String(rec_parentname))) {
              this.rec_parentnameError = true;
            }
            if (!/^([a-zA-ZäöüßÄÖÜẞ\u0600-\u06FF' ]+)$/.test(String(rec_lastname))) {
              this.rec_lastnameError = true;
            }
            if (!/^([a-zA-ZäöüßÄÖÜẞ\u0600-\u06FF' ]+)$/.test(String(city))) {
              this.cityError = true;
            }
            if (!/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,8}$/.test(String(rec_phone))) {
              this.rec_phoneError = true;
            }

            if (
              this.rec_firstnameError == false &&
              this.rec_parentnameError == false &&
              this.rec_lastnameError == false &&
              this.rec_phoneError == false &&
              this.cityError == false
            ) {
              this.screen_loader = true;
              this.itemService.getSelectedItems([mail_type], [], 0, this.lang).subscribe((response) => {
                this.responseData = response;
                if (this.responseData.message) {
                  this.screen_loader = false;
                  this.dataSource = this.responseData.selected_items;
                  this.cats = this.responseData.selected_cats;
                  this.total_price = this.responseData.total_price;
                  this.total_duration = this.responseData.total_duration;
                  this.mailInfo = {
                    rec_address: rec_address,
                    mail_type: mail_type,
                    city: city,
                    rec_firstname: rec_firstname + " " + rec_parentname,
                    rec_lastname: rec_lastname,
                    rec_phone: rec_phone,
                  };
                  this.mailInfoFlag = true;

                  stepper.next();
                }
              });
            }
          } else {
            this.mailFormGroup1 = this._formBuilder.group({
              mail_type: ["", Validators.required]
            });
            this.mailFormGroup2 = this._formBuilder.group({
              rec_firstname: ["", Validators.required],
              rec_parentname: ["", Validators.required],
              rec_lastname: ["", Validators.required],
              rec_address: ["", Validators.required],
              city: ["", Validators.required],
              rec_phone: ["", Validators.required],
            });
          }
        }
      }
    }
  }

  send() {
    if (this.indexTab == 1) {
      if (this.officialDocsFormGroup1.status == "VALID" && this.officialDocsFormGroup2.status == "VALID") {
        this.screen_loader = true;
        let data = {
          personalInfo: this.personalInfoFormGroup.getRawValue(),
          officialDocsInfo: {g1: this.officialDocsFormGroup1.getRawValue(), g2:this.officialDocsFormGroup2.getRawValue()},
          lang: this.lang,
          images: [this.preview1_compress, this.preview2_compress],
          total_duration: this.total_duration,
        };

        this.orderService.sendOrder({ data: data }).subscribe((response) => {
          this.responseData = response;
          this.screen_loader = false;
          if (this.responseData.message == "Your order sent successfully") {
            this._snackBar.openFromComponent(SnackbarComponent, {
              data: {
                html:
                  '<h2 style="color:red;">' +
                  this.responseData.success +
                  "</h2><h3>" +
                  this.responseData.order.order_id +
                  "</h3><div>" +
                  this.responseData.order.msg +
                  "</div>"
              },
              panelClass: ['msg-snackbar']
            });
            this.disable_reorder_btn = false;
          }
        });
      }
    } else {
      if (this.mailFormGroup1.status == "VALID" && this.mailFormGroup2.status == "VALID") {
        this.screen_loader = true;
        this.error_address = false;
        let data = {
          personalInfo: this.personalInfoFormGroup.getRawValue(),
          mailInfo: {g1: this.mailFormGroup1.getRawValue(), g2: this.mailFormGroup2.getRawValue()},
          lang: this.lang,
          total_duration: this.total_duration,
        };
        this.orderService.sendOrder({ data: data }).subscribe((response) => {
          this.responseData = response;
          this.screen_loader = false;
          if (this.responseData.message == "Your order sent successfully") {
            this._snackBar.openFromComponent(SnackbarComponent, {
              data: {
                html:
                  "<h2>" +
                  this.responseData.success +
                  "</h2><h3>" +
                  this.responseData.order.order_id +
                  "</h3><div>" +
                  this.responseData.order.msg +
                  "</div>"
              },
              panelClass: ['msg-snackbar']
            });
            this.disable_reorder_btn = false;
          }
        });
      } else {
        this.error_address = true;
      }
    }
  }

  reOrder() {
    location.reload();
    this.disable_reorder_btn = true;
  }

  onChangeCat(cat:any, type:any){
    this.screen_loader = true;
    this.itemService.getSelectedCats(cat.value, this.lang).subscribe((response) => {
      this.responseData = response;
      if (this.responseData.message) {
        this.screen_loader = false;
        if(type == 1){
          this.show_msg_cat1 = false;
          if(this.responseData.selected_cat.category_name == "سفارات اخرى" ||
            this.responseData.selected_cat.category_name == "Other embassies" ||
            this.responseData.selected_cat.category_name == "Andere Botschaften"
          ){
            this.show_msg_cat1 = true;
          }
        }
        else{
          this.show_langs_cat2 = false;
          this.officialDocsFormGroup1.controls.translate_language.setValue("");

          if(this.responseData.selected_cat.category_name == "مع ترجمة" ||
            this.responseData.selected_cat.category_name == "With translate" ||
            this.responseData.selected_cat.category_name == "Mit übersetzen"
          ){
            this.show_langs_cat2 = true;
            if (this.lang == "ar") {
              this.officialDocsFormGroup1.controls.translate_language.setValue("انكليزي");
            } else if (this.lang == "en") {
              this.officialDocsFormGroup1.controls.translate_language.setValue("English");
            } else if (this.lang == "de") {
              this.officialDocsFormGroup1.controls.translate_language.setValue("Englisch");
            }
          }
        }

      }
    });
  }

  onChangeLanguageCat(lang:any){
    this.show_msg_cat2 = false;
    if(lang.value == "اخر" || lang.value == "Andere" || lang.value == "Other"){
      this.show_msg_cat2 = true;
    }
  }



  validateIDNumber(){
    this.id_numberError = false;
    this.id_number = this.officialDocsFormGroup2.getRawValue().id_number?.trim();

    if (this.id_number != "" && !/^\d+$/.test(String(this.id_number))) {
      this.id_numberError = true;
    }
  }
  validateFirstname(){
    this.firstnameError = false;
    let firstname = this.personalInfoFormGroup.getRawValue().firstname?.trim();
    if (!/^([a-zA-ZäöüßÄÖÜẞ\u0600-\u06FF' ]+)$/.test(String(firstname))) {
      this.firstnameError = true;
    }
  }
  validateLastname(){
    this.lastnameError = false;
    let lastname = this.personalInfoFormGroup.getRawValue().lastname?.trim();
    if (!/^([a-zA-ZäöüßÄÖÜẞ\u0600-\u06FF' ]+)$/.test(String(lastname))) {
      this.lastnameError = true;
    }
  }
  validatePhone(){
    this.phoneError = false;
    let phone = this.personalInfoFormGroup.getRawValue().phone?.replace(/\s/g, '');
    if (!/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,8}$/.test(String(phone))) {
      this.phoneError = true;
    }
  }
  validateRecPhone(){
    this.rec_phoneError = false;
    let phone = this.mailFormGroup2.getRawValue().rec_phone?.replace(/\s/g, '');
    if (!/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,8}$/.test(String(phone))) {
      this.rec_phoneError = true;
    }
  }
  validateEmail(){
    this.emailError = false;
    let email = this.personalInfoFormGroup.getRawValue().email?.trim().toLowerCase();
    if (!/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,10}$/.test(String(email))) {
      this.emailError = true;
    }
  }

  validateRecFirstname(){
    this.rec_firstnameError = false;
    let rec_firstname = this.mailFormGroup2.getRawValue().rec_firstname?.trim();
    if (!/^([a-zA-ZäöüßÄÖÜẞ\u0600-\u06FF' ]+)$/.test(String(rec_firstname))) {
      this.rec_firstnameError = true;
    }
  }
  validateRecLastname(){
    this.rec_lastnameError = false;
    let rec_lastname = this.mailFormGroup2.getRawValue().rec_lastname?.trim();
    if (!/^([a-zA-ZäöüßÄÖÜẞ\u0600-\u06FF' ]+)$/.test(String(rec_lastname))) {
      this.rec_lastnameError = true;
    }
  }
  validateRecParentname(){
    this.rec_parentnameError = false;
    let rec_parentname = this.mailFormGroup2.getRawValue().rec_parentname?.trim();
    if (!/^([a-zA-ZäöüßÄÖÜẞ\u0600-\u06FF' ]+)$/.test(String(rec_parentname))) {
      this.rec_parentnameError = true;
    }
  }
  validateCity(){
    this.cityError = false;
    let city = this.mailFormGroup2.getRawValue().city?.trim();
    if (!/^([a-zA-ZäöüßÄÖÜẞ\u0600-\u06FF' ]+)$/.test(String(city))) {
      this.cityError = true;
    }
  }
}
