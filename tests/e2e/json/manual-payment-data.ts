export const getPaymentMethodsPaybillOnly = {
  status: "success",
  data: {
    enabled:true,
    till:"",
    paybill:{
      business_no:"141411",
      account_no:"767690"
    }
  },
};

export const saveManualPaymentInfo = {
  status: "info",
  message: "The payment reference number has been sent. The order will be updated once the payment has been verified."
}

export const saveManualPaymentSuccess = {
  status: "success",
  message: "You have paid KSh 32,000 to Systems Limited."
}
