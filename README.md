# ltw02g08

## Projeto

### Páginas incluídas

- index.html - Landing/Home page
- schedules.html - Lista e filtros de aulas
- admin-staff.html - Gestão de treinadores e staff (formulários + tabelas)

As 3 páginas usam componentes e estruturas diferentes para cobrir padrões variados de UI.

### Estrutura CSS

O styling está organizado em camadas num único ficheiro css/style.css:

- Base: variáveis CSS, reset e estilos globais de elementos HTML.
- Componentes: botões, cartões, formulários, tabelas, estados e padrões reutilizáveis.
- Casos específicos: ajustes por secção/página (hero, headers de página, áreas de gestão, etc.).

A estrutura da página admin-staff.html tem um design ligeiramente diferente, principalmente no menu, pois não pertence ao website (front-office) e vai futuramente fazer parte do back-office dos admins.

## Features

**All users:**
- [x] Register a new account.
- [x] Log in and out.
- [ ] Edit their profile, including name, username, password, and profile photo.

**Members:**
- [x] Browse the schedule of available fitness classes, filtering by type, trainer, day, or time.
- [x] Enroll in and cancel enrollment from upcoming classes, subject to capacity limits.
- [x] View trainer profiles, including their specializations and the classes they teach.
- [x] Check the current availability of equipment in the main training area.
- [x] Leave ratings and reviews for classes they have attended.

**Trainers:**
- [ ] Manage their public profile, including bio, specializations, and certifications.
- [x] View the roster of members enrolled in their classes.
- [x] Track and manage their assigned class schedule.

**Admins:**
- [x] Manage members and trainers (create, update, and deactivate accounts).
- [ ] Manage the class catalog (create, edit, and remove classes) and assign trainers to them.
- [ ] Manage equipment in the main training area (add, update availability status, and remove items).
- [ ] Elevate a user to admin status.
- [ ] Oversee and ensure the smooth operation of the entire system.

**Extra:**
- [x] Front-end responsive pages for home, schedules, and admin/staff management.

## Running

php -S localhost:9000

## Credentials

All default pilot accounts use the common password: `123456`
- **Membro**: `aluno@fithub.pt`
- **Treinador**: `joao@fithub.pt`
- **Administrador**: `admin@fithub.pt`
- **Staff**: `rita@fithub.pt`
