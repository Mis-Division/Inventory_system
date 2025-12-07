<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Suppliers;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function CreateSupplier(Request $request)
    {
        $request->validate([
            'supplier_no' => 'required|string|max:255',
            'AccountCode' => 'required|string|max:255',
            'supplier_name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'contact_no' => 'required|string|max:20',
             'contact_person' => 'required|string|max:255',
            'tin' => 'required|string|max:500',
            'vat_type' => 'required|string|max:500',
           
        ]);

       DB::beginTransaction();
       try {
           $supplier = suppliers::create([
               'supplier_no' => $request->supplier_no,
               'AccountCode' => $request->AccountCode,
               'supplier_name' => $request->supplier_name,
               'address' => $request->address,
               'contact_no' => $request->contact_no,
                'contact_person' => $request->contact_person,
               'tin' => $request->tin,
               'vat_type'=> $request->vat_type,
           ]);

           DB::commit();
           return response()->json(['message' => 'Supplier created successfully', 'supplier' => $supplier], 201);
       } catch (\Exception $e) {
           DB::rollBack();
           return response()->json(['error' => 'Failed to create supplier', 'message' => $e->getMessage()], 500);
       }
    }
  public function GetSuppliers(Request $request)
{
    try {
        // Get search and pagination parameters from the request
        $search = $request->input('search');
        $perPage = $request->input('per_page', 1000); // Default 10 per page

        // Query suppliers
        $query = Suppliers::query();

        // 🔍 Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                  ->orwhere('supplier_no', 'like', "%{$search}%")
                  ->orWhere('AccountCode', 'like', "%{$search}%")
                  ->orWhere('contact_no', 'like', "%{$search}%")
                   ->orWhere('supplier_name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // 📄 Paginate results
        $suppliers = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            // 'message' => 'Suppliers fetched successfully',
            'data' => $suppliers->items(),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to fetch suppliers',
            'message' => $e->getMessage()
        ], 500);
    }
}

 public function GetSupplierById($id)
{
    try {
        $supplier = Suppliers::find($id);

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found'
            ], 404);
        }

        return response()->json([
            // 'success' => true,
            // 'message' => 'Supplier fetched successfully',
            'data' => $supplier  // ✅ use "data" instead of "supplier"
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to fetch supplier',
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function UpdateSupplier(Request $request, $id)
    {
        $request->validate([
            'supplier_no' => 'sometimes|required|string|max:255',
            'AccountCode' => 'sometimes|required|string|max:255',
            'supplier_name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|nullable|string|max:500',
            'contact_no' => 'sometimes|nullable|string|max:20',
            'contact_person' => 'sometimes|nullable|string|max:255',
            'tin' => 'sometimes|nullable|string|max:500',
            'vat_type' => 'sometimes|nullable|string|max:500',
        ]);

       DB::beginTransaction();
       try {
           $supplier = suppliers::find($id);

           $supplier->update($request->only(['supplier_no','AccountCode', 'supplier_name', 'contact_no', 'address','contact_person','tin','vat_type']));

           DB::commit();
           return response()->json(['message' => 'Supplier updated successfully', 'supplier' => $supplier], 200);
       } catch (\Exception $e) {
           DB::rollBack();
           return response()->json(['error' => 'Failed tos update supplier', 'message' => $e->getMessage()], 500);
       }
    }
    public function DeleteSupplier($id)
    {
       DB::beginTransaction();
       try {
           $supplier = suppliers::find($id);

           if (!$supplier) {
               return response()->json(['error' => 'Supplier not found'], 404);
           }

           $supplier->delete();

           DB::commit();
           return response()->json(['message' => 'Supplier deleted successfully'], 200);
       } catch (\Exception $e) {
           DB::rollBack();
           return response()->json(['error' => 'Failed to delete supplier', 'message' => $e->getMessage()], 500);
       }
    }
    
}
