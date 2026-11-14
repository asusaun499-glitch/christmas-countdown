# Christmas Map Opening Countdown

หน้าเว็บเป็นพื้นหลังสีดำและแสดงเฉพาะตัวนับถอยหลังถึง 25 ธันวาคม 2569 เวลา 00:00 เขตเวลา Asia/Bangkok เมื่อถึงเวลาจะแสดงคำฉลอง จุดพลุ และลองเล่นเพลงโดยอัตโนมัติ มี PHP API สำหรับเวลาเซิร์ฟเวอร์

## แอดมิน

หน้าแอดมินอยู่ที่ `/admin.php` (หรือ `/admin` บน Vercel) ตั้ง environment variable `COUNTDOWN_ADMIN_PASSWORD` เป็นรหัสผ่านก่อนใช้งาน มีตัวเลือกตั้งเวลาเอง ทดสอบฉลองทันที/ใน 10 วินาที และรีเซ็ตกลับเป็น 25 ธันวาคม 2569 เวลา 00:00 น.

บน PHP hosting ทั่วไป การตั้งค่าจะบันทึกใน `data/countdown.json`. ในเครื่องให้ตั้งรหัสผ่านแล้วเปิด PHP server:

```powershell
$env:COUNTDOWN_ADMIN_PASSWORD = 'ตั้งรหัสผ่านของคุณ'
php -S 0.0.0.0:8000 -t christmas-countdown
```

จากนั้นเข้า `http://localhost:8000/admin.php`. Vercel Functions ใช้ระบบไฟล์ชั่วคราว/อ่านอย่างเดียว จึงต้องใช้ฐานข้อมูลถาวรสำหรับเวลาที่ปรับผ่านแอดมิน: รัน [supabase.sql](supabase.sql) ใน Supabase SQL Editor แล้วตั้ง `SUPABASE_URL` และ `SUPABASE_SERVICE_ROLE_KEY` เป็น Environment Variables ใน Vercel พร้อมตั้ง `COUNTDOWN_ADMIN_PASSWORD` ด้วย ค่าเหล่านี้ไม่ควรใส่ในไฟล์ที่อัปโหลดขึ้น GitHub.

## Vercel และเว็บ

ตั้งค่า **Root Directory** ของ Vercel เป็น `christmas-countdown`. `index.html` เป็นหน้าเว็บ, PHP endpoint อ่านเวลาปัจจุบัน/เวลาที่ตั้งไว้, และ `vercel.json` กำหนด PHP community runtime สำหรับ Vercel ซึ่งไม่ใช่ runtime ทางการของ Vercel. การนับถอยหลังรับการเปลี่ยนเวลาจากแอดมินภายในไม่กี่วินาที. เบราว์เซอร์บางตัวอาจบล็อกเสียงอัตโนมัติจนกว่าผู้เข้าชมจะแตะหน้าเว็บ.

ปรับไฟล์เพลงที่ `assets/jingle-bell-rock.mp3` ได้ พลุวาดด้วย Canvas และภาพเคลื่อนไหวใช้ GSAP/Google Fonts ผ่าน CDN.
