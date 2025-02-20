import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Features/FoodManagement/Presentation/Controllers/food_management_controller.dart';

class EditFoodScreen extends StatelessWidget {
  final Map<String, dynamic> food;

  EditFoodScreen({required this.food, Key? key}) : super(key: key);

  final FoodManagementController controller =
      Get.find<FoodManagementController>();

  final TextEditingController nameCtrl = TextEditingController();
  final TextEditingController priceCtrl = TextEditingController();
  final TextEditingController imageUrlCtrl = TextEditingController();

  @override
  Widget build(BuildContext context) {
    // مقداردهی اولیه به فیلدها (برای نمایش مقدار قبلی غذا)
    nameCtrl.text = food["name"] ?? "";
    priceCtrl.text = food["price"].toString();
    imageUrlCtrl.text = food["image_url"] ?? "";

    return Scaffold(
      appBar: AppBar(title: const Text("ویرایش غذا"), centerTitle: true),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildTextField(nameCtrl, "نام غذا", Icons.fastfood,
                isAlphabetic: true),
            _buildTextField(priceCtrl, "قیمت (تومان)", Icons.attach_money,
                isNumeric: true),
            _buildTextField(imageUrlCtrl, "لینک تصویر", Icons.image),

            const SizedBox(height: 24),

            // ✅ **دکمه ویرایش غذا**
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () async {
                  if (!_validateInputs()) return;

                  await controller.updateFood(
                    id: food["id"],
                    name: nameCtrl.text.trim(),
                    price: int.parse(priceCtrl.text.trim()),
                    imageUrl: imageUrlCtrl.text.trim(),
                  );
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.orange.shade700,
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
                child: const Text("✏️ ویرایش غذا",
                    style:
                        TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // 📌 **ویجت ساخت تکست‌فیلد**
  Widget _buildTextField(
      TextEditingController controller, String label, IconData icon,
      {bool isNumeric = false, bool isAlphabetic = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: TextField(
        controller: controller,
        keyboardType: isNumeric ? TextInputType.number : TextInputType.text,
        decoration: InputDecoration(
          labelText: label,
          prefixIcon: Icon(icon, color: Colors.blue),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          filled: true,
          fillColor: Colors.grey.shade100,
        ),
      ),
    );
  }

  // 🛑 **بررسی ورودی‌ها قبل از ارسال**
  bool _validateInputs() {
    if (nameCtrl.text.trim().isEmpty ||
        priceCtrl.text.trim().isEmpty ||
        imageUrlCtrl.text.trim().isEmpty) {
      Get.snackbar("خطا", "لطفاً همه فیلدها را پر کنید!",
          backgroundColor: Colors.red, colorText: Colors.white);
      return false;
    }

    if (!RegExp(r'^[\u0600-\u06FF\s]+$').hasMatch(nameCtrl.text.trim())) {
      Get.snackbar("خطا", "نام غذا باید فقط شامل حروف فارسی باشد!",
          backgroundColor: Colors.red, colorText: Colors.white);
      return false;
    }

    if (int.tryParse(priceCtrl.text.trim()) == null) {
      Get.snackbar("خطا", "قیمت باید یک عدد باشد!",
          backgroundColor: Colors.red, colorText: Colors.white);
      return false;
    }

    return true;
  }
}
