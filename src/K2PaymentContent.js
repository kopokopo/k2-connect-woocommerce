import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { Modal, MpesaNumberForm, PinInstruction } from './components';

/**
 * Ordered list of steps in the LNM payment flow.
 * Used to determine the sequence of screens in the checkout process.
 */
const LNMPaymentSteps = [
  'MpesaNumberForm',
  'PinInstruction',
  'Processing',
  'Response',
  'NoResponseInstruction'
]


const K2PaymentContent = ({ emitResponse, billing, eventRegistration }) => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [currentStep, _setCurrentStep] = useState('MpesaNumberForm');
  const [processingCheckout, setProcessingCheckout] = useState(false);
  const { onPaymentSetup } = eventRegistration;

  // Use this to restrict to only the defined LNMPaymentSteps[]
  const setCurrentStep = (step) => {
    if (LNMPaymentSteps.includes(step)) {
      _setCurrentStep(step);
    } else {
      console.warn(`Invalid LNMPaymentStep: ${step}`);
    }
  };

  const nextStep = () => {
    const currentIndex = LNMPaymentSteps.indexOf(currentStep);
    switch (LNMPaymentSteps[currentIndex]) {
      case 'MpesaNumberForm':
        setCurrentStep('PinInstruction');
        break;
      case 'PinInstruction':
        setCurrentStep('Processing');
    }
  };

  const resetSteps = () => {
    setCurrentStep('MpesaNumberForm');
  }

  const sendSTKPush = async () => {
    await new Promise((res) => setTimeout(res, 1000));
    return {
      success: true,
      message: 'STK Push sent successfully',
    };
  };

  const makePayment = async () => {
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

  }

  const handleStep = async () => {
     nextstep();
  };

  const handleCancel = () => {
    setIsModalOpen(false);
    resetSteps();
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

  const handleClose = () => {
    setIsModalOpen(false);
  }

  return (
    <>
      <p>Click "Proceed to Lipa na M-PESA" below to pay with M-PESA.</p>
      {isModalOpen &&
        createPortal(
          <Modal isOpen={isModalOpen} >
            { currentStep == 'MpesaNumberForm' && <MpesaNumberForm onCancel={handleCancel} onConfirm={handleStep} /> }
            { currentStep == 'PinInstruction' && <PinInstruction onClose={handleClose} /> }

          </Modal>,
          document.body
        )}
    </>
  );
};

export default K2PaymentContent;
