import { execSync } from 'node:child_process';

// Demo people, batches and a class running now (database/seeders/DemoSeeder.php).
// E2E_ARTISAN lets a machine run artisan another way, for example in a container.
export default function globalSetup() {
    const artisan = process.env.E2E_ARTISAN ?? 'php artisan';
    execSync(`${artisan} db:seed --class=DemoSeeder --force`, { stdio: 'inherit' });
}
