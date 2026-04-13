<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $driver;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->driver = Schema::getConnection()->getDriverName();

        foreach ([
            'outlets',
            'roles',
            'users',
            'units',
            'customers',
            'products',
            'vendors',
            'garment_types',
            'garment_type_measurements',
            'garment_type_tailoring_packages',
        ] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        Schema::table('outlets', function (Blueprint $table): void {
            $table->dropUnique('outlets_code_unique');
        });
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique('roles_name_unique');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_email_unique');
        });
        Schema::table('units', function (Blueprint $table): void {
            $table->dropUnique('units_code_unique');
        });
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('customers_email_unique');
            $table->dropUnique('customers_phone_unique');
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_code_unique');
            $table->dropUnique('products_barcode_unique');
        });
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropUnique('vendors_email_unique');
        });

        if ($this->driver === 'pgsql') {
            $this->createPostgresActiveUniqueIndexes();

            return;
        }

        $this->createMysqlActiveUniqueColumnsAndIndexes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->driver = Schema::getConnection()->getDriverName();

        if ($this->driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS vendors_email_active_unique');
            DB::statement('DROP INDEX IF EXISTS products_barcode_active_unique');
            DB::statement('DROP INDEX IF EXISTS products_code_active_unique');
            DB::statement('DROP INDEX IF EXISTS customers_phone_active_unique');
            DB::statement('DROP INDEX IF EXISTS customers_email_active_unique');
            DB::statement('DROP INDEX IF EXISTS units_code_active_unique');
            DB::statement('DROP INDEX IF EXISTS users_email_active_unique');
            DB::statement('DROP INDEX IF EXISTS roles_name_active_unique');
            DB::statement('DROP INDEX IF EXISTS outlets_code_active_unique');
        } else {
            Schema::table('vendors', function (Blueprint $table): void {
                $table->dropUnique('vendors_email_active_unique');
                $table->dropColumn('active_email');
            });

            Schema::table('products', function (Blueprint $table): void {
                $table->dropUnique('products_barcode_active_unique');
                $table->dropUnique('products_code_active_unique');
                $table->dropColumn(['active_barcode', 'active_code']);
            });

            Schema::table('customers', function (Blueprint $table): void {
                $table->dropUnique('customers_phone_active_unique');
                $table->dropUnique('customers_email_active_unique');
                $table->dropColumn(['active_phone', 'active_email']);
            });

            Schema::table('units', function (Blueprint $table): void {
                $table->dropUnique('units_code_active_unique');
                $table->dropColumn('active_code');
            });

            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_email_active_unique');
                $table->dropColumn('active_email');
            });

            Schema::table('roles', function (Blueprint $table): void {
                $table->dropUnique('roles_name_active_unique');
                $table->dropColumn('active_name');
            });

            Schema::table('outlets', function (Blueprint $table): void {
                $table->dropUnique('outlets_code_active_unique');
                $table->dropColumn('active_code');
            });
        }

        Schema::table('vendors', function (Blueprint $table): void {
            $table->unique('email');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->unique('code');
            $table->unique('barcode');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->unique('email');
            $table->unique('phone');
        });

        Schema::table('units', function (Blueprint $table): void {
            $table->unique('code');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('email');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->unique('name');
        });

        Schema::table('outlets', function (Blueprint $table): void {
            $table->unique('code');
        });

        foreach ([
            'garment_type_tailoring_packages',
            'garment_type_measurements',
            'garment_types',
            'vendors',
            'products',
            'customers',
            'units',
            'users',
            'roles',
            'outlets',
        ] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }

    private function createPostgresActiveUniqueIndexes(): void
    {
        DB::statement('CREATE UNIQUE INDEX outlets_code_active_unique ON outlets (code) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX roles_name_active_unique ON roles (name) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX users_email_active_unique ON users (email) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX units_code_active_unique ON units (code) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX customers_email_active_unique ON customers (email) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX customers_phone_active_unique ON customers (phone) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX products_code_active_unique ON products (code) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX products_barcode_active_unique ON products (barcode) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX vendors_email_active_unique ON vendors (email) WHERE deleted_at IS NULL');
    }

    private function createMysqlActiveUniqueColumnsAndIndexes(): void
    {
        Schema::table('outlets', function (Blueprint $table): void {
            $table->string('active_code')->nullable()->storedAs('IF(deleted_at IS NULL, code, NULL)');
            $table->unique('active_code', 'outlets_code_active_unique');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->string('active_name')->nullable()->storedAs('IF(deleted_at IS NULL, name, NULL)');
            $table->unique('active_name', 'roles_name_active_unique');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('active_email')->nullable()->storedAs('IF(deleted_at IS NULL, email, NULL)');
            $table->unique('active_email', 'users_email_active_unique');
        });

        Schema::table('units', function (Blueprint $table): void {
            $table->string('active_code', 50)->nullable()->storedAs('IF(deleted_at IS NULL, code, NULL)');
            $table->unique('active_code', 'units_code_active_unique');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->string('active_email', 100)->nullable()->storedAs('IF(deleted_at IS NULL, email, NULL)');
            $table->string('active_phone', 30)->nullable()->storedAs('IF(deleted_at IS NULL, phone, NULL)');
            $table->unique('active_email', 'customers_email_active_unique');
            $table->unique('active_phone', 'customers_phone_active_unique');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->string('active_code', 80)->nullable()->storedAs('IF(deleted_at IS NULL, code, NULL)');
            $table->string('active_barcode', 32)->nullable()->storedAs('IF(deleted_at IS NULL, barcode, NULL)');
            $table->unique('active_code', 'products_code_active_unique');
            $table->unique('active_barcode', 'products_barcode_active_unique');
        });

        Schema::table('vendors', function (Blueprint $table): void {
            $table->string('active_email', 150)->nullable()->storedAs('IF(deleted_at IS NULL, email, NULL)');
            $table->unique('active_email', 'vendors_email_active_unique');
        });
    }
};
