import { Component, Inject, OnInit } from '@angular/core';
import { MAT_SNACK_BAR_DATA, MatSnackBar } from '@angular/material/snack-bar';
import { ActivatedRoute } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';

@Component({
  selector: 'app-snackbar',
  templateUrl: './snackbar.component.html',
  styleUrls: ['./snackbar.component.css']
})
export class SnackbarComponent{
  direction_css !: string;
  lang: any;

  constructor(
    @Inject(MAT_SNACK_BAR_DATA) public data: any,
    public snackBar: MatSnackBar,
    private translate : TranslateService,
    private route: ActivatedRoute

  ) {
    translate.addLangs(['en', 'de', 'ar']);
    this.route.queryParams.subscribe(params => {
        this.lang = params['lang'];
        if(this.lang != undefined)
        {
          translate.setDefaultLang(this.lang);
          this.translate.use(this.lang);
          if(this.lang == "ar"){
            this.direction_css = "direction: rtl;"
          }
          else{
            this.direction_css = "direction: unset;"
          }

        }

    });

  }

  dismiss(snackBar :MatSnackBar){
    snackBar.dismiss();
  }

}
