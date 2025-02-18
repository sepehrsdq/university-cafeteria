import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Features/FirstPage/Presentation/Controllers/first_page_controller.dart';

class FirstPageScreen extends GetView<FirstPageController> {
  const FirstPageScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Image.asset(
                'assets/images/first_page.jpg',
                height: 300,
                width: 300,
              ),
              SizedBox(height: 16.0),

              /// **فیلد ورود نام کاربری**
              Container(
                width: 300,
                child: TextField(
                  controller: controller.usernameCtrl,
                  decoration: InputDecoration(
                    hintText: 'نام کاربری',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ),
              SizedBox(height: 16.0),

              /// **فیلد ورود رمز عبور**
              Obx(() => Container(
                    width: 300,
                    child: TextField(
                      controller: controller.passwordCtrl,
                      obscureText: controller.obscureText.value,
                      decoration: InputDecoration(
                        hintText: 'رمز عبور',
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        suffixIcon: IconButton(
                          onPressed: controller.togglePasswordVisibility,
                          icon: Icon(
                            controller.obscureText.value
                                ? Icons.visibility_off
                                : Icons.visibility,
                            color: Colors.grey,
                          ),
                        ),
                      ),
                    ),
                  )),
              SizedBox(height: 8.0),

              /// **متن راهنمای ورود**
              Text(
                'نام کاربری برای دانشجویان شماره ی دانشجویی می‌باشد',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.grey),
              ),
              SizedBox(height: 16.0),

              /// **دکمه ورود**
              Obx(() => ElevatedButton(
                    onPressed: controller.isLoading.value
                        ? null
                        : controller.loginAction, // غیرفعال در حالت لودینگ
                    child: controller.isLoading.value
                        ? CircularProgressIndicator(color: Colors.white)
                        : Text('ورود'),
                  )),
            ],
          ),
        ),
      ),
    );
  }
}
