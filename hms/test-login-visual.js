const { chromium } = require('playwright');

async function testLogin() {
    console.log('🧪 PROBANDO LOGIN UNIFICADO\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 1000
    });

    const page = await browser.newPage();

    try {
        // 1. Abrir login
        console.log('📄 Abriendo login...');
        await page.goto('http://localhost/hospital/hms/login.php');
        await page.waitForLoadState('networkidle');

        // 2. Probar login de paciente
        console.log('\n🧪 Probando login como PACIENTE...');
        await page.fill('input[name="email"]', 'test@gmail.com');
        await page.fill('input[name="password"]', 'Hospital@2024');
        await page.click('button[name="submit"]');
        await page.waitForLoadState('networkidle');

        console.log('URL actual:', page.url());

        if (page.url().includes('dashboard1.php')) {
            console.log('✅ LOGIN PACIENTE: EXITOSO!\n');
        } else {
            console.log('❌ LOGIN PACIENTE: FALLÓ\n');
        }

        await page.waitForTimeout(2000);

        // 3. Logout y probar doctor
        await page.goto('http://localhost/hospital/hms/logout.php');
        await page.goto('http://localhost/hospital/hms/login.php');

        console.log('🧪 Probando login como DOCTOR...');
        await page.fill('input[name="email"]', 'anuj.lpu1@gmail.com');
        await page.fill('input[name="password"]', 'Hospital@2024');
        await page.click('button[name="submit"]');
        await page.waitForLoadState('networkidle');

        console.log('URL actual:', page.url());

        if (page.url().includes('doctor/dashboard.php')) {
            console.log('✅ LOGIN DOCTOR: EXITOSO!\n');
        } else {
            console.log('❌ LOGIN DOCTOR: FALLÓ\n');
        }

        await page.waitForTimeout(2000);

        // 4. Probar admin
        await page.goto('http://localhost/hospital/hms/logout.php');
        await page.goto('http://localhost/hospital/hms/login.php');

        console.log('🧪 Probando login como ADMIN...');
        await page.fill('input[name="email"]', 'admin@hospital.com');
        await page.fill('input[name="password"]', 'Test@12345');
        await page.click('button[name="submit"]');
        await page.waitForLoadState('networkidle');

        console.log('URL actual:', page.url());

        if (page.url().includes('admin/dashboard.php')) {
            console.log('✅ LOGIN ADMIN: EXITOSO!\n');
        } else {
            console.log('❌ LOGIN ADMIN: FALLÓ\n');
        }

        console.log('\n✅ PRUEBAS COMPLETADAS!');
        console.log('💡 El navegador quedará abierto para que explores...\n');

        // Mantener navegador abierto
        await new Promise(() => {});

    } catch (error) {
        console.error('❌ Error:', error.message);
    }
}

testLogin();
