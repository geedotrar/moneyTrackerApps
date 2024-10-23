<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionRoleTable extends Migration
{
    public function up()
    {
        Schema::create('permission_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->string('permission_name');
            $table->foreign('permission_name')->references('name')->on('permissions')->onDelete('cascade'); // Menambahkan foreign key
            $table->unique(['role_id', 'permission_name']); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('permission_role');
    }
}
