/**
 * Dialog component for creating new orders
 * Includes form fields for customer name, product, and quantity
 */
import { useFormWithDialog } from '@/Hooks/useFormWithDialog';
import { toast } from 'sonner';

import FormDialog from '@/Components/Common/FormDialog';
import FormField from '@/Components/Common/FormField';
import { Button } from '@/Components/ui/button';

export default function CreateOrderDialog({ trigger }) {
  const { data, setData, errors, processing, submit, dialog, handleDialogChange } =
    useFormWithDialog(
      {
        customer_name: '',
        product: '',
        quantity: '',
      },
      {
        route: 'orders.store', // Update with actual route
        method: 'post',
        onSuccess: () => {
          toast.success('Order created successfully!', {
            description: 'Your order has been created.',
          });
        },
      }
    );

  return (
    <FormDialog
      open={dialog.isOpen}
      onOpenChange={handleDialogChange}
      title="Create New Order"
      description="Fill in the details below to create a new order."
      trigger={trigger}
      confirmLabel="Create Order"
      cancelLabel="Cancel"
      processing={processing}
      processingLabel="Creating..."
      formId="create-order-form"
    >
      <form id="create-order-form" onSubmit={submit} className="space-y-4">
        <FormField
          name="customer_name"
          label="Customer Name"
          value={data.customer_name}
          onChange={(e) => setData('customer_name', e.target.value)}
          error={errors.customer_name}
          placeholder="Enter customer name"
        />

        <FormField
          name="product"
          label="Product"
          value={data.product}
          onChange={(e) => setData('product', e.target.value)}
          error={errors.product}
          placeholder="Enter product name"
        />

        <FormField
          name="quantity"
          label="Quantity"
          type="number"
          value={data.quantity}
          onChange={(e) => setData('quantity', e.target.value)}
          error={errors.quantity}
          placeholder="Enter quantity"
        />
      </form>
    </FormDialog>
  );
}
