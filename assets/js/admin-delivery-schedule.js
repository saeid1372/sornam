// استخراج useState و useEffect از wp.element (کتابخانه ری‌اکت وردپرس)
const { useState, useEffect } = wp.element;

// استخراج کامپوننت‌های وردپرس برای UI
const { Button, SelectControl, Card, CardBody, Spinner, Notice } = wp.components;

// برای درخواست‌های REST از apiFetch وردپرس استفاده می‌کنیم
const apiFetch = wp.apiFetch;

// لیست شهرهای استان کرمان
const KERMAN_CITIES = [
	"کرمان","رفسنجان","جیرفت","بم","زرند","سیرجان","کهنوج","راور","بافت",
	"بردسیر","عنبرآباد","رودبار جنوب","فهرج","قلعه گنج","ریگان","منوجان",
	"شهربابک","ارزوئیه","فاریاب","نرماشیر","انار","رابر","فهرج جدید"
];

// روزهای هفته
const DAYS = ["شنبه","یک‌شنبه","دوشنبه","سه‌شنبه","چهارشنبه","پنج‌شنبه","جمعه"];

// کامپوننت اصلی پنل ادمین
function App() {

	// state برای نگهداری داده‌های زمان‌بندی
	const [schedule, setSchedule] = useState({});

	// state برای لود اولیه دیتا
	const [loading, setLoading] = useState(true);

	// state برای نمایش پیام "ذخیره شد"
	const [saved, setSaved] = useState(false);

	// در اولین بار باز شدن پنل، داده‌ها از REST API دریافت می‌شوند
	useEffect(() => {
		apiFetch({ url: wcFspData.rest_url })
			.then(data => setSchedule(data))       // ذخیره داده دریافت شده
			.finally(() => setLoading(false));     // پایان لود
	}, []);

	// آپدیت روز انتخاب شده برای هر شهر
	const updateDay = (city, day) => {
		setSchedule(prev => ({ ...prev, [city]: day }));
	};

	// ذخیره اطلاعات به سرور
	const handleSave = () => {
		setSaved(false);
		apiFetch({
			url: wcFspData.rest_url,
			method: 'POST',
			data: schedule,                        // ارسال زمان‌بندی
			headers: { 'X-WP-Nonce': wcFspData.nonce }
		}).then(() => {
			setSaved(true);                        // نمایش پیام موفقیت
		});
	};

	// نمایش لودر در صورت لود اولیه
	if (loading) {
        return wp.element.createElement(wp.components.Spinner);
    }

    // کانتینر اصلی
    return wp.element.createElement(
        'div',
        { className: 'wc-fsp-admin p-4', style: { maxWidth: '800px' } },
        // Card
        wp.element.createElement(
            wp.components.Card,
            null,
            wp.element.createElement(
                wp.components.CardBody,
                null,
                // عنوان
                wp.element.createElement('h2', null, 'زمان‌بندی ارسال رایگان شهرستان‌های کرمان'),
                // توضیح
                wp.element.createElement('p', null, 'برای هر شهرستان، روز ارسال رایگان را مشخص کنید:'),
                // پیام موفقیت ذخیره شدن
                saved
                    ? wp.element.createElement(
                         wp.components.Notice,
                         { status: 'success', isDismissible: false },
                         'تنظیمات ذخیره شد'
                      )
                    : null,
                // جدول
                wp.element.createElement(
                    'table',
                    { className: 'widefat striped' },
                    wp.element.createElement(
                         'thead',
                         null,
                         wp.element.createElement(
                             'tr',
                             null,
                             wp.element.createElement('th', null, 'شهرستان'),
                             wp.element.createElement('th', null, 'روز ارسال رایگان')
                        )
                    ),
                    wp.element.createElement(
                        'tbody',
                        null,
                        KERMAN_CITIES.map(city =>
                             wp.element.createElement(
                                 'tr',
                                 { key: city },
                                 wp.element.createElement('td', null, city),
                                 wp.element.createElement(
                                     'td',
                                     null,
                                     wp.element.createElement(wp.components.SelectControl, {
                                         value: schedule[city] || '',
                                         options: [
                                             { label: '-- انتخاب روز --', value: '' },
                                             ...DAYS.map(day => ({ label: day, value: day })),
                                            ],
                                            onChange: day => updateDay(city, day),
                                        })
                                    )
                            )
                        )
                    )
                ),
                // دکمه ذخیره
                wp.element.createElement(
                     'div',
                      { style: { marginTop: '20px' } },
                      wp.element.createElement(
                          wp.components.Button,
                         { variant: 'primary', onClick: handleSave },
                         'ذخیره تنظیمات'
                        )
                )
            )
        )
    );
}

// رندر کامپوننت در داخل دیو مشخص شده در HTML
wp.element.render(
	wp.element.createElement(App),
	document.getElementById('wc-fsp-admin-app')
);
