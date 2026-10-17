import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { MyOrderComponent } from './components/my-order/my-order.component';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, MyOrderComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css'
})
export class AppComponent {
  
}
