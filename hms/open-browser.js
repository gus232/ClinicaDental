const { chromium } = require('playwright');

async function openBrowser() {
    console.log('🌐 Abriendo navegador para explorar el sistema Hospital HMS...\n');

    const browser = await chromium.launch({
        headless: false,  // Navegador visible
        slowMo: 1000,     // Acciones lentas para que puedas ver
        args: ['--start-maximized']
    });

    const context = await browser.newContext({
        viewport: null  // Usar viewport completo
    });

    const page = await context.newPage();

    console.log('📄 Abriendo página de login...');
    await page.goto('http://localhost/hospital/hms/user-login.php');

    console.log('\n✅ Navegador abierto!');
    console.log('🔍 Explora el sistema manualmente desde el navegador.');
    console.log('\n📋 URLs importantes:');
    console.log('   - Login Paciente: http://localhost/hospital/hms/user-login.php');
    console.log('   - Login Admin:    http://localhost/hospital/hms/admin/');
    console.log('   - Login Doctor:   http://localhost/hospital/hms/doctor/');
    console.log('   - Registro:       http://localhost/hospital/hms/registration.php');
    console.log('\n👥 Credenciales de prueba:');
    console.log('   Paciente: test@gmail.com / Hospital@2024');
    console.log('   Admin:    admin / Test@12345');
    console.log('   Doctor:   anuj.lpu1@gmail.com / Hospital@2024');
    console.log('\n⚠️  NOTA: El login tiene bugs - puede no funcionar correctamente');
    console.log('💡 Presiona Ctrl+C cuando termines de explorar para cerrar el navegador.\n');

    // Mantener el navegador abierto
    await new Promise(() => {}); // Esperar indefinidamente
}

openBrowser().catch(console.error);
