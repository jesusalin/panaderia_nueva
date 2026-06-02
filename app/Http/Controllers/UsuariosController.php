<?php
namespace App\Http\Controllers;
use App\Models\Usuario;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuariosController extends Controller
{
    public function index() {
        $usuarios = Usuario::with('rol')->orderBy('nombre')->paginate(15);
        return view('usuarios.index', compact('usuarios'));
    }
    public function create() {
        $roles = Rol::orderBy('nombre')->get();
        return view('usuarios.create', compact('roles'));
    }
    public function store(Request $request) {
        $request->validate(['nombre'=>'required|string|max:100','usuario'=>'required|unique:usuarios,usuario|max:100','email'=>'required|email|unique:usuarios,email','password'=>'required|min:6|confirmed','id_rol'=>'required|exists:roles,id','estado'=>'required|in:activo,inactivo']);
        Usuario::create(['nombre'=>$request->nombre,'usuario'=>$request->usuario,'email'=>$request->email,'password'=>Hash::make($request->password),'id_rol'=>$request->id_rol,'estado'=>$request->estado]);
        return redirect()->route('usuarios.index')->with('success','Usuario creado.');
    }
    public function show(Usuario $usuario) {
        return view('usuarios.show', compact('usuario'));
    }
    public function edit(Usuario $usuario) {
        $roles = Rol::orderBy('nombre')->get();
        return view('usuarios.edit', compact('usuario','roles'));
    }
    public function update(Request $request, Usuario $usuario) {
        $request->validate(['nombre'=>'required|string|max:100','email'=>'required|email|unique:usuarios,email,'.$usuario->id,'id_rol'=>'required|exists:roles,id','estado'=>'required|in:activo,inactivo','password'=>'nullable|min:6|confirmed']);
        $data = $request->only(['nombre','email','id_rol','estado']);
        if ($request->filled('password')) $data['password'] = Hash::make($request->password);
        $usuario->update($data);
        return redirect()->route('usuarios.index')->with('success','Usuario actualizado.');
    }
    public function destroy(Usuario $usuario) {
        $usuario->update(['estado'=>'inactivo']);
        return redirect()->route('usuarios.index')->with('success','Usuario desactivado.');
    }
}
