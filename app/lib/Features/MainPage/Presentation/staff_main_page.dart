import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uni_project/Features/FoodManagement/Presentation/Controllers/food_management_controller.dart';
import 'package:uni_project/Features/MainPage/Presentation/Controllers/staff_main_controller.dart';
import 'package:uni_project/Features/Reports/Presentation/Controllers/reports_controller.dart';
import 'package:uni_project/Features/StudentSelf/Presentation/Controllers/student_self_controller.dart';
import 'package:uni_project/Features/Tickets/Presentation/Controllers/staff_tickets_controller.dart';

class StaffMainPageScreen extends GetView<StaffMainController> {
  const StaffMainPageScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Self Plus", style: TextStyle(fontSize: 18)),
        centerTitle: true,
        actions: [
          Padding(
            padding:
                const EdgeInsets.symmetric(horizontal: 16), // متعادل‌سازی فاصله
            child: Obx(() {
              return controller.staffName.value.isNotEmpty
                  ? Row(
                      mainAxisSize: MainAxisSize.min, // جلوگیری از اشغال کل فضا
                      children: [
                        Text(
                          controller.staffName.value,
                          style: const TextStyle(fontSize: 16),
                        ),
                        const SizedBox(width: 8), // فاصله بین متن و آیکون
                        const CircleAvatar(
                          radius: 18,
                          backgroundColor: Colors.grey,
                          child:
                              Icon(Icons.person, size: 22, color: Colors.white),
                        ),
                      ],
                    )
                  : const SizedBox();
            }),
          ),
        ],
        leading: IconButton(
          onPressed: () {
            _showLogoutDialog();
          },
          icon: const Icon(Icons.exit_to_app, color: Colors.red),
          tooltip: 'خروج از حساب کاربری',
          padding: const EdgeInsets.fromLTRB(0, 0, 32, 0),
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.fromLTRB(32, 32, 32, 16.0),
        child: GridView.builder(
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
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
                  case "مدیریت غذا":
                    final foodManagementController =
                        Get.put(FoodManagementController());
                    await foodManagementController.fetchFoods();
                    controller.routeFoodManagement();
                    break;

                  case "سلف دانشجویی":
                    final studentselfController =
                        Get.put(StudentSelfController());
                    await studentselfController.fetchReservations();
                    controller.routeStudentSelf();
                    break;

                  case "گزارشات":
                    final reportController = Get.put(ReportsController());
                    await reportController.fetchTotalReservations();
                    await reportController.fetchIncomeReport();
                    await reportController.fetchPopularMeals();
                    controller.routeReports();
                    break;

                  case "تیکت":
                    final staffticketsController =
                        Get.put(StaffTicketsController());
                    await staffticketsController.fetchTickets();
                    controller.routeStaffTickets();
                    break;
                  case "اضافه کردن دانشجوی جدید":
                    controller.addStudent();
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
        await prefs.clear();
        Get.offAllNamed('/firstpage');
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

// دسته‌بندی‌ها
final List<Map<String, dynamic>> categories = [
  {'icon': Icons.food_bank, 'label': "مدیریت غذا"},
  {'icon': Icons.people, 'label': "سلف دانشجویی"},
  {'icon': Icons.bar_chart, 'label': "گزارشات"},
  {'icon': Icons.message, 'label': "تیکت"},
  {'icon': Icons.add, 'label': "اضافه کردن دانشجوی جدید"},
];

class CategoryItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const CategoryItem({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      splashColor: const Color(0xFFF9D4D4),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFFF9D4D4),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
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
            const SizedBox(height: 16),
            Text(
              label,
              textAlign: TextAlign.center,
              style: const TextStyle(
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
