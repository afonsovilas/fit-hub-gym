# ltw02g08

## Features

**All users:**
- [x] Register a new account.
- [x] Log in and out.
- [x] Edit their profile, including name, username, password, and profile photo.

**Members:**
- [x] Browse the schedule of available fitness classes, filtering by type, trainer, day, or time.
- [x] Enroll in and cancel enrollment from upcoming classes, subject to capacity limits.
- [x] View trainer profiles, including their specializations and the classes they teach.
- [x] Check the current availability of equipment in the main training area.
- [x] Leave ratings and reviews for classes they have attended.

**Trainers:**
- [x] Manage their public profile, including bio, specializations, and certifications.
- [x] View the roster of members enrolled in their classes.
- [x] Track and manage their assigned class schedule.

**Admins:**
- [x] Manage members and trainers (create, update, and deactivate accounts).
- [x] Manage the class catalog (create, edit, and remove classes) and assign trainers to them.
- [x] Manage equipment in the main training area (add, update availability status, and remove items).
- [x] Elevate a user to admin status.
- [x] Oversee and ensure the smooth operation of the entire system.

**Extra:**
- [x] **Sistema de Lista de Espera Automatizado**: Membros juntam-se à lista de espera de aulas cheias. Se houver um cancelamento, o primeiro da lista é promovido e inscrito automaticamente, recebendo uma notificação.
- [x] **Marcação de Treinos Personalizados**: Agendamento individual com notas e objetivos. Treinadores gerem pedidos (Aceitar/Recusar) e as sessões confirmadas são fundidas e ordenadas no seu calendário semanal.
- [x] **Sistema de Planos de Nutrição**: Membros podem solicitar planos alimentares aos especialistas. Os treinadores gerem as solicitações pendentes e definem/atualizam prescrições de nutrição personalizadas.
- [x] **Editor de Recorte de Foto de Perfil Interativo**: Drag, zoom e corte centrado da foto de perfil em tempo real usando Canvas do HTML5 para exportar imagens perfeitamente quadradas.

## Running

    sqlite3 database/database.db < database/schema.sql
    php -S localhost:9000

## Credentials

- admin@fithub.pt/123456
- aluno@fithub.pt/123456
- joao@fithub.pt/123456
