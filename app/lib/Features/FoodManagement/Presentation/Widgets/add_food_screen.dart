import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Features/FoodManagement/Presentation/Controllers/food_management_controller.dart';

class AddFoodScreen extends StatelessWidget {
  final FoodManagementController controller =
      Get.find<FoodManagementController>();

  final TextEditingController nameCtrl = TextEditingController();
  final TextEditingController priceCtrl = TextEditingController();
  final TextEditingController dateCtrl = TextEditingController();
  final TextEditingController imageUrlCtrl = TextEditingController();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("افزودن غذا"), centerTitle: true),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildTextField(nameCtrl, "نام غذا", Icons.fastfood,
                isAlphabetic: true),
            _buildTextField(priceCtrl, "قیمت (تومان)", Icons.attach_money,
                isNumeric: true),
            _buildTextField(
                dateCtrl, "تاریخ (YYYY-MM-DD)", Icons.calendar_today,
                isDate: true),
            _buildTextField(imageUrlCtrl, "لینک تصویر", Icons.image),

            const SizedBox(height: 16),

            // 🍽 **انتخاب نوع غذا**
            Obx(() => Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.grey.shade400),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<String>(
                      value: controller.selectedType.value,
                      isExpanded: true,
                      items: const [
                        DropdownMenuItem(
                            value: "breakfast", child: Text("🍞 صبحانه")),
                        DropdownMenuItem(
                            value: "lunch", child: Text("🍛 ناهار")),
                        DropdownMenuItem(
                            value: "dinner", child: Text("🍲 شام")),
                        DropdownMenuItem(
                            value: "carmandi", child: Text("🏢 سلف کارمندی")),
                      ],
                      onChanged: (value) {
                        if (value != null) controller.changeType(value);
                      },
                    ),
                  ),
                )),

            const SizedBox(height: 24),

            // ✅ **دکمه‌ی افزودن غذا**
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () async {
                  if (!_validateInputs()) return;

                  await controller.addFood(
                    name: nameCtrl.text.trim(),
                    type: controller.selectedType.value,
                    price: int.parse(priceCtrl.text.trim()),
                    date: dateCtrl.text.trim(),
                    imageUrl: imageUrlCtrl.text.trim(),
                  );
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue,
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
                child: const Text("افزودن غذا",
                    style: TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.bold)),
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
      {bool isNumeric = false,
      bool isDate = false,
      bool isAlphabetic = false}) {
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
        dateCtrl.text.trim().isEmpty ||
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

    RegExp datePattern = RegExp(r'^\d{4}-\d{2}-\d{2}$');
    if (!datePattern.hasMatch(dateCtrl.text.trim())) {
      Get.snackbar("خطا", "فرمت تاریخ باید YYYY-MM-DD باشد!",
          backgroundColor: Colors.red, colorText: Colors.white);
      return false;
    }

    return true;
  }
}
