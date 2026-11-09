// Календарь вместо стандартного выбора даты
(function () {
    const MONTHS = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
    const DAYS = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
    const ICON = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>';

    // Дату показываем как дд.мм.гггг
    const toText = value => value.split('-').reverse().join('.');
    // Добавляем ведущий ноль
    const two = number => String(number).padStart(2, '0');

    // Закрываем открытый календарь
    function closeCalendar() {
        // Заодно закрываем открытый список
        document.querySelectorAll('.cal, .sel-list').forEach(popup => popup.remove());
    }

    // Рисуем месяц
    function draw(popup, input) {
        const year = +popup.dataset.year;
        const month = +popup.dataset.month;
        // Понедельник считаем первым днём недели
        const shift = (new Date(year, month, 1).getDay() + 6) % 7;
        const count = new Date(year, month + 1, 0).getDate();
        const now = new Date();
        const today = now.getFullYear() + '-' + two(now.getMonth() + 1) + '-' + two(now.getDate());

        let days = '';
        for (let day = 1; day <= count; day++) {
            const iso = year + '-' + two(month + 1) + '-' + two(day);
            const classes = (iso === input.value ? 'sel ' : '') + (iso === today ? 'today' : '');
            days += '<button type="button" data-day="' + iso + '" class="' + classes + '">' + day + '</button>';
        }

        popup.innerHTML = '<div class="cal-head">'
            + '<button type="button" data-step="-1" aria-label="Предыдущий месяц">‹</button>'
            + MONTHS[month] + ' ' + year
            + '<button type="button" data-step="1" aria-label="Следующий месяц">›</button></div>'
            + '<div class="cal-grid">' + DAYS.map(name => '<span>' + name + '</span>').join('')
            + '<i></i>'.repeat(shift) + days + '</div>';
    }

    // Заменяем каждое поле даты своей кнопкой
    document.querySelectorAll('input[type="date"]').forEach(function (input) {
        const box = document.createElement('div');
        box.className = 'dp';
        input.parentNode.insertBefore(box, input);
        // Настоящее значение остаётся в скрытом поле формы
        input.type = 'hidden';
        box.appendChild(input);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'dp-btn';
        button.setAttribute('aria-label', 'Выбрать дату');
        button.innerHTML = '<span></span>' + ICON;
        box.appendChild(button);

        // Обновляем текст на кнопке
        function showValue() {
            const text = button.querySelector('span');
            text.textContent = input.value ? toText(input.value) : 'дд.мм.гггг';
            text.className = input.value ? '' : 'dp-empty';
        }
        showValue();

        button.addEventListener('click', function () {
            const wasOpen = box.querySelector('.cal');
            closeCalendar();
            if (wasOpen) {
                return;
            }
            const base = input.value ? new Date(input.value + 'T00:00') : new Date();
            const popup = document.createElement('div');
            popup.className = 'cal';
            popup.dataset.year = base.getFullYear();
            popup.dataset.month = base.getMonth();
            box.appendChild(popup);
            draw(popup, input);

            popup.addEventListener('click', function (event) {
                const target = event.target.closest('button');
                if (!target) {
                    return;
                }
                // Переход на другой месяц
                if (target.dataset.step) {
                    const next = new Date(+popup.dataset.year, +popup.dataset.month + +target.dataset.step, 1);
                    popup.dataset.year = next.getFullYear();
                    popup.dataset.month = next.getMonth();
                    draw(popup, input);
                }
                // Выбор дня
                if (target.dataset.day) {
                    input.value = target.dataset.day;
                    showValue();
                    closeCalendar();
                }
            });
        });
    });

    // Значок раскрытия списка
    const CHEVRON = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';

    // Заменяем каждый список своим
    document.querySelectorAll('select').forEach(function (select) {
        const box = document.createElement('div');
        box.className = 'dp';
        select.parentNode.insertBefore(box, select);
        // Настоящий список скрыт, но отправляется с формой
        select.hidden = true;
        box.appendChild(select);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'dp-btn';
        button.setAttribute('aria-haspopup', 'listbox');
        button.innerHTML = '<span></span>' + CHEVRON;
        box.appendChild(button);

        // Показываем выбранный пункт
        function showValue() {
            const option = select.options[select.selectedIndex];
            button.querySelector('span').textContent = option ? option.textContent : '';
        }
        showValue();

        button.addEventListener('click', function () {
            const wasOpen = box.querySelector('.sel-list');
            closeCalendar();
            if (wasOpen) {
                return;
            }
            const list = document.createElement('div');
            list.className = 'sel-list';
            list.setAttribute('role', 'listbox');

            // Создаём пункты списка
            Array.from(select.options).forEach(function (option, index) {
                const item = document.createElement('button');
                item.type = 'button';
                item.textContent = option.textContent;
                item.className = index === select.selectedIndex ? 'sel' : '';
                item.addEventListener('click', function () {
                    select.selectedIndex = index;
                    showValue();
                    closeCalendar();
                    // Список сортировки сразу открывает нужный адрес
                    if ('go' in select.dataset) {
                        location.href = select.value;
                    }
                });
                list.appendChild(item);
            });
            box.appendChild(list);
        });
    });

    // Клик мимо и Esc закрывают календарь и список
    document.addEventListener('click', function (event) {
        if (!event.composedPath().some(node => node.classList && node.classList.contains('dp'))) {
            closeCalendar();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeCalendar();
        }
    });
})();
