<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\suppliers;
use Illuminate\Support\Facades\DB;

class Supplier extends Controller
{
    public function CreateSupplier(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

       DB::beginTransaction();
       try {
           $supplier = suppliers::create([
               'supplier_name' => $request->supplier_name,
               'contact_person' => $request->contact_person,
               'email' => $request->email,
               'phone' => $request->phone,
               'address' => $request->address,
           ]);

           DB::commit();
           return response()->json(['message' => 'Supplier created successfully', 'supplier' => $supplier], 201);
       } catch (\Exception $e) {
           DB::rollBack();
           return response()->json(['error' => 'Failed to create supplier', 'message' => $e->getMessage()], 500);
       }
    }
    public function GetSuppliers()
    {
       try{
              $suppliers = suppliers::all();
              return response()->json(['suppliers' => $suppliers], 200);
         } catch (\Exception $e) {
              return response()->json(['error' => 'Failed to fetch suppliers', 'message' => $e->getMessage()], 500);
       }
    }
    public function GetSupplierById($id)
    {
       try{
              $supplier = suppliers::find($id);
              if (!$supplier) {
                  return response()->json(['error' => 'Supplier not found'], 404);
              }
              return response()->json(['supplier' => $supplier], 200);
         } catch (\Exception $e) {
              return response()->json(['error' => 'Failed to fetch supplier', 'message' => $e->getMessage()], 500);
       }
    }
    public function UpdateSupplier(Request $request, $id)
    {
        $request->validate([
            'supplier_name' => 'sometimes|required|string|max:255',
            'contact_person' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:500',
        ]);

       DB::beginTransaction();
       try {
           $supplier = suppliers::find($id);

           $supplier->update($request->only(['supplier_name', 'contact_person', 'email', 'phone', 'address']));

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
