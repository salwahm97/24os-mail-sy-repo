import { AbstractControl } from '@angular/forms';

export function orderCodeValidator(control: AbstractControl): { [key: string]: boolean } | null {
  let value = control.value;
  if(!value){
    return null;
  }
  if(value.charAt(0) == "D" || value.charAt(0) == "M")
  {
    let num = value.slice(1);
    if(Number.isInteger(Number(num))){
      return null;
    }
    else{
      return {codeInvalid: true};

    }
  }
  else{
    if(Number.isInteger(Number(value))){
      return null;
    }
    else{
      return {codeInvalid: true};
    }
  }

}
