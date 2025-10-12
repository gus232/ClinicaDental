const { chromium } = require('playwright');

async function exploreHospitalSystem() {
    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const context = await browser.newContext();
    const page = await context.newPage();

    const baseUrl = 'http://localhost/hospital/hms';
    const report = {
        pages: [],
        forms: [],
        buttons: [],
        links: []
    };

    console.log('🏥 Explorando Sistema Hospital - HMS\n');

    try {
        // 1. Página de Login
        console.log('📄 1. PÁGINA DE LOGIN');
        await page.goto(`${baseUrl}/user-login.php`);
        await page.waitForLoadState('networkidle');

        const loginButtons = await page.$$eval('button, input[type="submit"]', buttons =>
            buttons.map(b => ({ text: b.textContent || b.value, type: b.type }))
        );
        console.log('   Botones:', loginButtons);

        const loginLinks = await page.$$eval('a', links =>
            links.map(l => ({ text: l.textContent.trim(), href: l.href })).filter(l => l.text)
        );
        console.log('   Enlaces:', loginLinks.map(l => l.text));

        // Login como paciente
        console.log('\n🔐 Intentando login como paciente...');
        await page.fill('input[name="username"]', 'test@gmail.com');
        await page.fill('input[name="password"]', 'Hospital@2024');
        await page.click('button[type="submit"], input[type="submit"]');
        await page.waitForLoadState('networkidle');

        // 2. Dashboard de Paciente
        console.log('\n📄 2. DASHBOARD DE PACIENTE');
        console.log('   URL actual:', page.url());

        const dashboardButtons = await page.$$eval('button, input[type="submit"], a.btn', elements =>
            elements.map(el => ({ text: el.textContent.trim() || el.value, class: el.className }))
        );
        console.log('   Botones/Enlaces:', dashboardButtons.filter(b => b.text));

        // Verificar menú lateral
        const sidebarLinks = await page.$$eval('.sidebar a, nav a, .menu a', links =>
            links.map(l => ({ text: l.textContent.trim(), href: l.href }))
        );
        console.log('   Menú lateral:', sidebarLinks.map(l => l.text).filter(t => t));

        // 3. Explorar "Book Appointment"
        console.log('\n📄 3. AGENDAR CITA (Book Appointment)');
        const bookAppointmentLink = await page.$('a[href*="book-appointment"]');
        if (bookAppointmentLink) {
            await bookAppointmentLink.click();
            await page.waitForLoadState('networkidle');

            const formFields = await page.$$eval('input, select, textarea', fields =>
                fields.map(f => ({
                    name: f.name,
                    type: f.type || f.tagName.toLowerCase(),
                    placeholder: f.placeholder
                }))
            );
            console.log('   Campos del formulario:', formFields.filter(f => f.name));

            const appointmentButtons = await page.$$eval('button, input[type="submit"]', buttons =>
                buttons.map(b => ({ text: b.textContent.trim() || b.value }))
            );
            console.log('   Botones:', appointmentButtons.filter(b => b.text));
        }

        // 4. Explorar "Appointment History"
        console.log('\n📄 4. HISTORIAL DE CITAS');
        const historyLink = await page.$('a[href*="appointment-history"], a[href*="history"]');
        if (historyLink) {
            await historyLink.click();
            await page.waitForLoadState('networkidle');

            const tableHeaders = await page.$$eval('th', headers =>
                headers.map(h => h.textContent.trim())
            );
            console.log('   Columnas de tabla:', tableHeaders);

            const actionButtons = await page.$$eval('button, a.btn, input[type="submit"]', buttons =>
                buttons.map(b => ({ text: b.textContent.trim() || b.value }))
            );
            console.log('   Acciones disponibles:', actionButtons.filter(b => b.text));
        }

        // 5. Explorar "Medical History"
        console.log('\n📄 5. HISTORIAL MÉDICO');
        const medHistoryLink = await page.$('a[href*="medhistory"], a[href*="medical"]');
        if (medHistoryLink) {
            await medHistoryLink.click();
            await page.waitForLoadState('networkidle');
            console.log('   URL:', page.url());
        }

        // 6. Logout y login como Admin
        console.log('\n🔐 Logout y login como ADMIN...');
        await page.goto(`${baseUrl}/logout.php`);
        await page.waitForLoadState('networkidle');

        // Login Admin
        await page.goto(`${baseUrl}/admin`);
        await page.waitForLoadState('networkidle');

        console.log('\n📄 6. PANEL DE ADMIN - LOGIN');
        const adminLoginButtons = await page.$$eval('button, input[type="submit"]', buttons =>
            buttons.map(b => ({ text: b.textContent || b.value }))
        );
        console.log('   Botones:', adminLoginButtons);

        // Intentar login como admin (asumiendo credenciales)
        const usernameField = await page.$('input[name="username"]');
        if (usernameField) {
            await page.fill('input[name="username"]', 'admin');
            await page.fill('input[name="password"]', 'Test@12345');
            await page.click('button[type="submit"], input[type="submit"]');
            await page.waitForLoadState('networkidle');

            console.log('\n📄 7. DASHBOARD DE ADMIN');
            console.log('   URL:', page.url());

            const adminSidebar = await page.$$eval('.sidebar a, nav a, .menu a', links =>
                links.map(l => ({ text: l.textContent.trim(), href: l.href }))
            );
            console.log('   Opciones de menú:', adminSidebar.map(l => l.text).filter(t => t));
        }

        // Logout y login como Doctor
        console.log('\n🔐 Logout y login como DOCTOR...');
        await page.goto(`${baseUrl}/admin/logout.php`);
        await page.waitForLoadState('networkidle');

        await page.goto(`${baseUrl}/doctor`);
        await page.waitForLoadState('networkidle');

        console.log('\n📄 8. PANEL DE DOCTOR - LOGIN');

        const doctorUsernameField = await page.$('input[name="username"]');
        if (doctorUsernameField) {
            await page.fill('input[name="username"]', 'anuj.lpu1@gmail.com');
            await page.fill('input[name="password"]', 'Hospital@2024');
            await page.click('button[type="submit"], input[type="submit"]');
            await page.waitForLoadState('networkidle');

            console.log('\n📄 9. DASHBOARD DE DOCTOR');
            console.log('   URL:', page.url());

            const doctorSidebar = await page.$$eval('.sidebar a, nav a, .menu a', links =>
                links.map(l => ({ text: l.textContent.trim(), href: l.href }))
            );
            console.log('   Opciones de menú:', doctorSidebar.map(l => l.text).filter(t => t));
        }

        console.log('\n✅ Exploración completada!');

    } catch (error) {
        console.error('❌ Error durante exploración:', error.message);
    } finally {
        await browser.close();
    }
}

exploreHospitalSystem();
