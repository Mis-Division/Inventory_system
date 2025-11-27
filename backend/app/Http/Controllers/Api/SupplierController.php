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
            'supplier_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'contact_no' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'tin' => 'required|string|max:500',
            'vat_no' => 'required|string|max:500',
        ]);

       DB::beginTransaction();
       try {
           $supplier = suppliers::create([
               'supplier_no' => $request->supplier_no,
               'supplier_name' => $request->supplier_name,
               'email' => $request->email,
               'contact_no' => $request->contact_no,
               'address' => $request->address,
               'tin' => $request->tin,
               'vat_no'=> $request->vat_no,
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
        $perPage = $request->input('per_page', 10); // Default 10 per page

        // Query suppliers
        $query = Suppliers::query();

        // 🔍 Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                  ->orwhere('supplier_no', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_no', 'like', "%{$search}%")
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
            'supplier_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'contact_no' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:500',
            'tin' => 'sometimes|nullable|string|max:500',
            'vat_no' => 'sometimes|nullable|string|max:500',
        ]);

       DB::beginTransaction();
       try {
           $supplier = suppliers::find($id);

           $supplier->update($request->only(['supplier_no', 'supplier_name', 'email', 'contact_no', 'address','tin','vat_no']));

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
