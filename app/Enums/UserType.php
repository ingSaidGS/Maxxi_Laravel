<?php

namespace App\Enums;

/**
 * Tipo de usuario del sistema, base de los permisos por capacidad.
 *
 * - Encargado: administra catálogo, compras, reportes y usuarios.
 * - Empleado: opera ventas, notas de venta y alistamiento (picking).
 */
enum UserType: string
{
    case Encargado = 'encargado';
    case Empleado = 'empleado';

    /**
     * Nombre legible del tipo para mostrar en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::Encargado => 'Encargado',
            self::Empleado => 'Empleado',
        };
    }
}
