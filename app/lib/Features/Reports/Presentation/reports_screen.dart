import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Features/Reports/Presentation/Controllers/reports_controller.dart';

class ReportsScreen extends StatelessWidget {
  final ReportsController controller = Get.put(ReportsController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('گزارشات سلف', style: TextStyle(fontSize: 18)),
        centerTitle: true,
        leading: IconButton(
          onPressed: controller.routeMainPage,
          icon: const Icon(Icons.keyboard_backspace),
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.all(8.0),
        child: Obx(() {
          if (controller.isLoading.value) {
            return Center(child: CircularProgressIndicator());
          }

          return SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // 🔹 تعداد کل رزروهای امروز
                _buildInfoCard("تعداد کل رزروهای امروز",
                    controller.totalReservationsToday.value.toString()),

                // 🔹 درآمد روزانه و ماهانه
                _buildInfoCard(
                    "درآمد روزانه", "${controller.dailyIncome.value} تومان"),
                _buildInfoCard(
                    "درآمد ماهانه", "${controller.monthlyIncome.value} تومان"),

                // 🔹 غذاهای پرطرفدار
                Text("🍽️ پرطرفدارترین غذاها",
                    style:
                        TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                _buildMealList(controller.popularMeals),

                // 🔹 غذاهای کم‌طرفدار
                Text("🥄 کم‌طرفدارترین غذاها",
                    style:
                        TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                _buildMealList(controller.leastPopularMeals),
              ],
            ),
          );
        }),
      ),
    );
  }

  // ✅ ویجت کارت اطلاعات
  Widget _buildInfoCard(String title, String value) {
    return Card(
      margin: EdgeInsets.symmetric(vertical: 6),
      child: ListTile(
        title: Text(title, style: TextStyle(fontWeight: FontWeight.bold)),
        trailing: Text(value,
            style: TextStyle(fontSize: 16, color: Colors.blueAccent)),
      ),
    );
  }

  // ✅ ویجت نمایش لیست غذاها
  Widget _buildMealList(List<dynamic> meals) {
    return Column(
      children: meals.map((meal) {
        String jalaliDate = meal['last_reserved'] != null
            ? ReportsController().convertToShamsi(meal['last_reserved'])
            : "نامشخص";

        return Card(
          margin: EdgeInsets.symmetric(vertical: 4),
          child: ListTile(
            title: Text(meal['name'],
                style: TextStyle(fontWeight: FontWeight.bold)),
            subtitle: Text("تعداد رزرو: ${meal['reservation_count']}"),
            trailing: Text("آخرین رزرو: $jalaliDate"),
          ),
        );
      }).toList(),
    );
  }
}
