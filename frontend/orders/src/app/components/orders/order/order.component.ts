import { Component, Input } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { TranslateService } from '@ngx-translate/core';
import { LangService } from 'src/app/services/lang.service';
import { NotificationService, toastrPayload } from 'src/app/services/notification.service';
import { OrderService } from 'src/app/services/order.service';
import { StatusService } from 'src/app/services/status.service';
import { OrderStatusDialogComponent } from '../../dialogs/add-order-status-dialog/order-status-dialog.component';
import { MatTableDataSource } from '@angular/material/table';
import { IndividualConfig } from 'ngx-toastr';

@Component({
  selector: 'app-order',
  templateUrl: './order.component.html',
  styleUrls: ['./order.component.css']
})
export class OrderComponent {
  @Input() inputFromParent!: any;
  dir:any = "ltr";
  screen_loader: boolean = false;
  toast!: toastrPayload;
  responseData: any;
  responseStatusData: any;

  order_is_docs: boolean = false;
  order_is_mail: boolean = false;
  order: any;
  error_msg: boolean = false;

  displayedColumns: string[] = ["item_name", "item_duration", "item_price", "item_desc"];
  dataSource: any[] = [];

  displayedColumnsStatus: string[] = ["status", "owner", "notes", "img", "date"];
  statusDataSource!: MatTableDataSource<any>;

  order_type: any;
  adminLang: any;

  constructor(
    private orderService: OrderService,
    private statusService: StatusService,
    private translate: TranslateService,
    private serviceLang: LangService,
    private dialog: MatDialog,
    private cs: NotificationService
  ) {
    translate.addLangs(["en", "de", "ar"]);
  }


  ngOnInit(){
    this.serviceLang.getLang().subscribe(response => {
      this.adminLang = response;
      this.translate.use(this.adminLang);
      if(this.adminLang == "ar"){
        this.dir = "rtl";
      }
    });
    if(Number.isInteger(Number(this.inputFromParent)) && Number(this.inputFromParent) > 0 && this.inputFromParent != undefined){
      let order_id = this.inputFromParent;
      this.screen_loader = true;
      this.error_msg = false;
      this.orderService.getOrderById(order_id).subscribe((response) => {
        this.responseData = response;
        if(this.responseData['order'])
        {
          this.order = this.responseData['order'];
          this.dataSource = this.responseData['order']['items'];

          this.statusService.getOrderStatuses(order_id).subscribe((response) => {
            this.responseStatusData = response;
            this.screen_loader = false;
            this.statusDataSource = new MatTableDataSource(this.responseStatusData.order_statuses);

          });
          console.log(this.order);

          if((this.responseData['order']['type'] == "old" && this.responseData['order']['rec_firstname'] != null) ||
              this.responseData['order']['type'] == "mail")
          {
            // order is mail
            this.order_is_docs = false;
            this.order_is_mail = true;
            this.order_type = "mail";
          }

          if((this.responseData['order']['type'] == "old" && this.responseData['order']['rec_firstname'] == null) ||
              this.responseData['order']['type'] == "docs")
          {
            // order is docs
            this.order_is_mail = false;
            this.order_is_docs = true;
            this.order_type = "docs";
          }
        }
        else{
          this.error_msg = true;
        }

      });
    }

  }

  openDialogAddStatus(order_id: any, order_type: any, order_email: any, order_lang: any, order_no: any): void {
    this.screen_loader = true;
    this.statusService.getStatuses().subscribe((response) => {
      let responseData: any = response;
      this.screen_loader = false;
      let statuses = responseData.statuses.filter((st:any) => st.type === order_type);

      const dialogRef = this.dialog.open(OrderStatusDialogComponent, {
        width:'650px',
        panelClass: 'fullscreen-dialog',
        data:{
          order_id: order_id,
          order_email: order_email,
          order_lang: order_lang,
          order_no: order_no,
          statuses: statuses,
          adminLang: this.adminLang,
          dir:this.dir},
      });

      dialogRef.afterClosed().subscribe(result => {
        console.log('The dialog was closed ');
        if(result != false && result != undefined){
          this.screen_loader = true;
          let status = "Success";
          let msg = result;
          this.translate.get(status).subscribe(t=>{status = t;});
          this.translate.get(msg).subscribe(t=>{msg = t;});
          this.toastShowTimeOut("success", status, msg);
          this.statusService.getOrderStatuses(order_id).subscribe((response) => {
            this.responseStatusData = response;
            this.screen_loader = false;
            this.statusDataSource = new MatTableDataSource(this.responseStatusData.order_statuses);

          });
        }

      });
    });

  }

  goToBack(){
    window.location.assign(
      window.location.href.split("?")[0]+'?page=24os-mail-sy'
    );
  }

  toastShowTimeOut(type: string, title: string, message:string){
    this.toast = {
      message: message,
      title: title,
      type: type,
      ic:{
        positionClass: "toast-bottom-right",
      } as IndividualConfig,
    };
    this.cs.showToast(this.toast);
  }
}
