import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shamsi_date/shamsi_date.dart';
import 'package:uni_project/Features/FoodManagement/Presentation/Controllers/food_management_controller.dart';

import 'package:uni_project/Features/FoodManagement/Presentation/Widgets/add_food_screen.dart';
import 'package:uni_project/Features/FoodManagement/Presentation/Widgets/edit_food_screen.dart';

class FoodManagementScreen extends StatelessWidget {
  final FoodManagementController controller =
      Get.put(FoodManagementController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("مدیریت غذا", style: TextStyle(fontSize: 18)),
        centerTitle: true,
        leading: IconButton(
          onPressed: controller.routeMainPage,
          icon: const Icon(Icons.keyboard_backspace),
        ),
        actions: [
          PopupMenuButton<String>(
            onSelected: (String value) => controller.filterFoods(value),
            itemBuilder: (context) => [
              const PopupMenuItem(value: "", child: Text("همه")),
              const PopupMenuItem(value: "breakfast", child: Text("صبحانه")),
              const PopupMenuItem(value: "lunch", child: Text("ناهار")),
              const PopupMenuItem(value: "dinner", child: Text("شام")),
              const PopupMenuItem(
                  value: "carmandi", child: Text("سلف کارمندی")),
            ],
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: Obx(() {
              if (controller.isLoading.value) {
                return const Center(child: CircularProgressIndicator());
              }

              return ListView.builder(
                physics: const BouncingScrollPhysics(),
                itemCount: controller.filteredFoods.length,
                itemBuilder: (context, index) {
                  final food = controller.filteredFoods[index];
                  return _buildFoodItem(food);
                },
              );
            }),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => Get.to(() => AddFoodScreen()),
        backgroundColor: Colors.blue,
        child: const Icon(Icons.add, size: 24, color: Colors.white),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        mini: true,
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.startFloat,
    );
  }

  Widget _buildFoodItem(Map<String, dynamic> food) {
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
      child: ListTile(
        leading: ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: Image.network(
            food["image_url"],
            width: 70,
            height: 70,
            fit: BoxFit.cover,
            errorBuilder: (context, error, stackTrace) {
              return const Icon(Icons.image_not_supported,
                  size: 70, color: Colors.grey);
            },
          ),
        ),
        title: Text(food["name"],
            style: const TextStyle(fontWeight: FontWeight.bold)),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text("قیمت: ${food["price"]} تومان"),
            Text("تاریخ عرضه: ${_convertToShamsiDate(food["date"])}"),
          ],
        ),
        trailing: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            IconButton(
              icon: const Icon(Icons.edit, color: Colors.orange),
              onPressed: () => Get.to(() => EditFoodScreen(food: food)),
            ),
            IconButton(
              icon: const Icon(Icons.delete, color: Colors.red),
              onPressed: () => controller.deleteFood(food["id"]),
            ),
          ],
        ),
      ),
    );
  }

  String _convertToShamsiDate(String date) {
    DateTime dateTime = DateTime.parse(date);
    Jalali jalaliDate = Jalali.fromDateTime(dateTime);
    return "${jalaliDate.year}/${jalaliDate.month}/${jalaliDate.day}";
  }
}
