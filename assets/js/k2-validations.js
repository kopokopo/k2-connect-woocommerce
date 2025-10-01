(function($){
  window.KKWooValidations = window.KKWooValidations || {}
  window.KKWooValidations.validMpesaNumber  = (phone) => {
    const $error = $(".message.error");

    if (!phone) {
      $error.text("Phone number is required.").show();
      return false;
    }
    
    if (!/^\d{9}$/.test(phone)) {
      $error.text("Phone number must be 9 digits.").show();
      return false;
    }

    $error.hide();
    return true;
  }

  window.KKWooValidations.validMpesaRefNo  = (mpesaRefNo) => {
    const $error = $(".message.error");

    if (!mpesaRefNo) {
      $error.text("M-PESA reference number is required.").show();
      return false;
    }
  
    $error.hide();
    return true;
  }
})(jQuery)
