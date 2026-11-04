<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserType;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'tipo',
        'active',
        'access_start_time',
        'access_end_time',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tipo' => UserType::class,
            'active' => 'boolean',
        ];
    }

    /**
     * Indica si el usuario es encargado.
     */
    public function isEncargado(): bool
    {
        return $this->tipo === UserType::Encargado;
    }

    /**
     * Indica si el usuario es empleado.
     */
    public function isEmpleado(): bool
    {
        return $this->tipo === UserType::Empleado;
    }

    /**
     * Indica si el usuario tiene un horario de acceso configurado.
     *
     * Se exige que estén ambas horas: una sola no define un rango válido.
     */
    public function hasAccessSchedule(): bool
    {
        return filled($this->access_start_time) && filled($this->access_end_time);
    }

    /**
     * Indica si el usuario puede acceder en el momento indicado.
     *
     * Los encargados y los usuarios sin horario siempre tienen acceso. El fin
     * del rango es exclusivo (a las 19:00 ya no se permite el acceso cuando el
     * horario termina a esa hora). El rango debe tener la entrada antes que la
     * salida; si los datos almacenados no lo cumplen, no se concede acceso.
     */
    public function isWithinAccessSchedule(?CarbonInterface $moment = null): bool
    {
        if ($this->isEncargado() || ! $this->hasAccessSchedule()) {
            return true;
        }

        $now = ($moment ?? now())->format('H:i:s');
        $start = Carbon::parse($this->access_start_time)->format('H:i:s');
        $end = Carbon::parse($this->access_end_time)->format('H:i:s');

        return $now >= $start && $now < $end;
    }

    /**
     * Texto legible del horario para mostrar en la interfaz.
     */
    public function accessScheduleLabel(): string
    {
        if ($this->isEncargado() || ! $this->hasAccessSchedule()) {
            return 'Sin restricción';
        }

        return substr($this->access_start_time, 0, 5).' a '.substr($this->access_end_time, 0, 5);
    }
}
