import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uni_project/Features/BreakFast/Presentation/Controllers/break_fast_controller.dart';
import 'package:uni_project/Features/Dinner/Presentation/Controllers/dinner_controller.dart';
import 'package:uni_project/Features/Lunch/Presentation/Controllers/lunch_controller.dart';
import 'package:uni_project/Features/MainPage/Presentation/Controllers/main_page_controller.dart';
import 'package:uni_project/Features/SelfCarmandi/Presentation/Controllers/self_carmandi_controller.dart';
import 'package:uni_project/Features/Tickets/Presentation/Controllers/tickets_controller.dart';

class MainPageScreen extends GetView<MainPageController> {
  const MainPageScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Self Plus", style: TextStyle(fontSize: 18)),
        centerTitle: true,
        leading: IconButton(
          onPressed: () {
            _showLogoutDialog();
          },
          icon: const Icon(Icons.exit_to_app, color: Colors.red),
          tooltip: 'خروج از حساب کاربری',
          padding: const EdgeInsets.fromLTRB(0, 0, 32, 0),
        ),
        actions: <Widget>[
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Obx(() => Row(
                  mainAxisSize: MainAxisSize.min, // جلوگیری از اشغال کل فضا
                  children: [
                    Text(
                      '${controller.balance.value} تومان',
                      style: const TextStyle(fontSize: 16),
                    ),
                    const SizedBox(width: 8), // فاصله بین متن و آیکون
                    const Icon(Icons.attach_money,
                        color: Colors.green, size: 28),
                  ],
                )),
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.fromLTRB(32, 32, 32, 16.0),
        child: GridView.builder(
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            crossAxisSpacing: 20,
            mainAxisSpacing: 10,
            childAspectRatio: 1.4,
          ),
          itemCount: categories.length,
          itemBuilder: (context, index) {
            return CategoryItem(
              icon: categories[index]['icon'],
              label: categories[index]['label'],
              onTap: () async {
                switch (categories[index]['label']) {
                  case "صبحانه":
                    final breakFastController = Get.put(BreakFastController());
                    await breakFastController.fetchBreakfasts();
                    controller.routeBreakFast();
                    break;

                  case "سلف کارمندی":
                    final selfCarmandiController =
                        Get.put(SelfCarmandiController());
                    await selfCarmandiController.fetchSelfCarmandi();
                    controller.routeSelfCarmandi();
                    break;

                  case "ناهار":
                    final lunchController = Get.put(LunchController());
                    await lunchController.fetchLunches();
                    controller.routeLunch();
                    break;

                  case "شام":
                    final dinnerController = Get.put(DinnerController());
                    await dinnerController.fetchDinners();
                    controller.routeDinner();
                    break;
                  case "تیکت":
                    final ticketsController = Get.put(TicketsController());
                    await ticketsController.fetchTickets();
                    controller.routeTickets();
                    break;
                  default:
                    print("هیچ گزینه‌ای یافت نشد!");
                }
              },
            );
          },
        ),
      ),
    );
  }
}

void _showLogoutDialog() {
  Get.snackbar(
    'خروج از حساب',
    'آیا از خروج مطمئن هستید؟',
    snackPosition: SnackPosition.BOTTOM,
    duration: const Duration(seconds: 3),
    mainButton: TextButton(
      onPressed: () async {
        SharedPreferences prefs = await SharedPreferences.getInstance();
        await prefs.clear(); // حذف تمام اطلاعات ذخیره‌شده
        Get.offAllNamed('/firstpage'); // برگشت به صفحه لاگین
      },
      child: const Text('بله', style: TextStyle(color: Colors.white)),
    ),
    backgroundColor: Colors.redAccent,
    colorText: Colors.white,
    padding: const EdgeInsets.all(16),
    margin: const EdgeInsets.all(16),
    borderRadius: 10,
    overlayBlur: 2,
    animationDuration: const Duration(milliseconds: 500),
    icon: const Icon(Icons.warning, color: Colors.white),
  );
}

// داده‌های مربوط به دسته‌بندی‌ها
final List<Map<String, dynamic>> categories = [
  {'icon': Icons.coffee, 'label': "صبحانه"},
  {'icon': Icons.local_pizza, 'label': "سلف کارمندی"},
  {'icon': Icons.local_restaurant_sharp, 'label': "ناهار"},
  {'icon': Icons.ramen_dining, 'label': "شام"},
  {'icon': Icons.message, 'label': "تیکت"},
];

class CategoryItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const CategoryItem(
      {required this.icon, required this.label, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      splashColor: Color(0xFFF9D4D4),
      child: Container(
        padding: EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Color(0xFFF9D4D4),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                icon,
                size: 40,
                color: Colors.grey[800],
              ),
            ),
            SizedBox(height: 16),
            Text(
              label,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
