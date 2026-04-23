<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\OrganizationInterface;
use App\Repositories\OrganizationRepository;
use App\Repositories\Interfaces\ConfigurationInterface;
use App\Repositories\ConfigurationRepository;
use App\Repositories\Interfaces\UserInterface;
use App\Repositories\UserRepository;
use App\Repositories\Interfaces\DepartmentInterface;
use App\Repositories\DepartmentRepository;
use App\Repositories\Interfaces\OperatorInterface;
use App\Repositories\OperatorRepository;
use App\Repositories\Interfaces\ActivityLogInterface;
use App\Repositories\ActivityLogRepository;
use App\Repositories\Interfaces\DockInterface;
use App\Repositories\DockRepository;
use App\Repositories\Interfaces\DeviceInterface;
use App\Repositories\DeviceRepository;
use App\Repositories\Interfaces\ProfileInterface;
use App\Repositories\ProfileRepository;


class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrganizationInterface::class, OrganizationRepository::class);
        $this->app->bind(ConfigurationInterface::class, ConfigurationRepository::class);
        $this->app->bind(UserInterface::class, UserRepository::class);
        $this->app->bind(DepartmentInterface::class, DepartmentRepository::class);
        $this->app->bind(OperatorInterface::class, OperatorRepository::class);
        $this->app->bind(ActivityLogInterface::class, ActivityLogRepository::class);
        $this->app->bind(DockInterface::class, DockRepository::class);
        $this->app->bind(DeviceInterface::class, DeviceRepository::class);
        $this->app->bind(ProfileInterface::class, ProfileRepository::class);
    }

    public function boot(): void {}
}
