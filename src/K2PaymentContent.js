import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import Modal from './components/modal.js';

const K2PaymentContent = ({ emitResponse, billing, eventRegistration }) => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [processingCheckout, setProcessingCheckout] = useState(false);
  const { onPaymentSetup } = eventRegistration;

  // Fallback modal opener via custom event
  useEffect(() => {
    const openModal = () => setIsModalOpen(true);
    window.addEventListener('kkwoo-open-modal', openModal);
    return () => window.removeEventListener('kkwoo-open-modal', openModal);
  }, []);

  // Handle Woo Blocks payment flow integration
  useEffect(() => {
    if (typeof onPaymentSetup !== 'function') {
      console.warn('onPaymentSetup is not available or not a function.');
      return;
    }

    const unsubscribe = onPaymentSetup(() => {
      if (processingCheckout) return;

      setIsModalOpen(true);
      setProcessingCheckout(true);

      return new Promise((resolve, reject) => {
        window.kkwooPaymentResolve = resolve;
        window.kkwooPaymentReject = reject;

        emitResponse?.error?.({
          message: 'Please complete payment in modal.',
        });
      });
    });

    return () => unsubscribe?.();
  }, [processingCheckout, emitResponse, onPaymentSetup]);

  // STK Push simulation
  const sendSTKPush = async () => {
    await new Promise((res) => setTimeout(res, 1000));
    return {
      success: true,
      message: 'STK Push sent successfully',
    };
  };

  const handleConfirm = async () => {
    const stkResult = await sendSTKPush();

    const payload = {
      confirmed: true,
      timestamp: new Date().toISOString(),
      stkResult,
    };

    setIsModalOpen(false);
    setProcessingCheckout(false);

    if (typeof window.kkwooPaymentResolve === 'function') {
      window.kkwooPaymentResolve({
        type: 'success',
        meta: {
          paymentMethodData: payload,
          billingData: payload,
        },
      });
    }

    window.kkwooPaymentResolve = null;
    window.kkwooPaymentReject = null;

    emitResponse?.success?.({ paymentMethodData: payload });
  };

  const handleCancel = () => {
    setIsModalOpen(false);
    setProcessingCheckout(false);

    if (typeof window.kkwooPaymentReject === 'function') {
      window.kkwooPaymentReject({
        type: 'error',
        message: 'Payment cancelled by user.',
        messageContext: 'error',
      });
    }

    window.kkwooPaymentResolve = null;
    window.kkwooPaymentReject = null;

    emitResponse?.error?.({ message: 'Payment cancelled by user.' });
  };

  return (
    <>
      <p>Pay using Lipa na M-Pesa. Modal will appear after clicking "Lipa na M-Pesa".</p>
      {isModalOpen &&
        createPortal(
          <Modal title='Lipa na M-PESA' isOpen={isModalOpen} onConfirm={handleConfirm} onCancel={handleCancel}>
            <div class='amount-card'>
              <div class='label'>
                Amount to pay
              </div>
              <div class='amount'>
                Ksh 32,000
              </div>
            </div>
            <form>
              <div class='form-group'>
                <label>Enter M-PESA phone number</label>
                <div class='amount-input'>
                  <span class='country-code'>
                    <img src={window.KKWooData.kenyan_flag_img} alt='Kenyan flag' class='k2'/> 
                    <span> +254</span>
                  </span>
                  <input type='text' placeholder='7xx xxx xxx'/>
                </div>
              </div>
            </form>
          </Modal>,
          document.body
        )}
    </>
  );
};

export default K2PaymentContent;
