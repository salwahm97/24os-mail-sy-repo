import { Component, ViewChild } from '@angular/core';
import { MatSort } from '@angular/material/sort';
import { MatPaginator } from '@angular/material/paginator';
import { MatTableDataSource } from '@angular/material/table';
import { TranslateService } from '@ngx-translate/core';
import { toastrPayload } from 'src/app/services/notification.service';
import { OrderService } from 'src/app/services/order.service';
import { LangService } from 'src/app/services/lang.service';

@Component({
  selector: 'app-orders',
  templateUrl: './orders.component.html',
  styleUrls: ['./orders.component.css']
})
export class OrdersComponent {
  dir:any = "ltr";
  screen_loader: boolean = false;
  toast!: toastrPayload;

  ordersDataSource!: MatTableDataSource<any>;
  @ViewChild('paginator') paginator!: MatPaginator;
  @ViewChild('sort') sort = new MatSort();

  displayedColumnsOrders: string[] = ['no', 'id', 'order', 'fullname', 'email', 'type', 'date'];

  responseData: any;
  order_id: any;

  constructor(
    private orderService: OrderService,
    private translate: TranslateService,
    private serviceLang: LangService
  ) {
    translate.addLangs(["en", "de", "ar"]);
  }

  ngOnInit() {
    this.serviceLang.getLang().subscribe(response => {
      let adminLang: any = response;
      this.translate.use(adminLang);
      if(adminLang == "ar"){
        this.dir = "rtl";
      }
    });

    let paramsString = window.location.search;
    let searchParams = new URLSearchParams(paramsString);

    if(Number.isInteger(Number(searchParams.get("order_id"))) && Number(searchParams.get("order_id")) > 0 && searchParams.get("order_id") != undefined){
      this.order_id = Number(searchParams.get("order_id"));
    }
    else{
      this.screen_loader = true;
      this.orderService.getOrders().subscribe((response) => {
        this.responseData = response;
        this.screen_loader = false;
        console.log(this.responseData);

        this.ordersDataSource = new MatTableDataSource(this.responseData['allOrders']);
        this.ordersDataSource.paginator = this.paginator;
        this.ordersDataSource.sort = this.sort;
      });

    }
  }

  viewOrder(id: any){
    window.location.assign(
      window.location.href.split("?")[0]+'?page=24os-mail-sy&order_id='+id
    );
  }
}
