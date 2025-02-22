import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Features/AddStudent/Presentation/Controllers/add_student_controller.dart';

class AddStudentScreen extends StatelessWidget {
  final AddStudentController controller = Get.put(AddStudentController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('افزودن دانشجو', style: TextStyle(fontSize: 18)),
        centerTitle: true,
        leading: IconButton(
          onPressed: controller.routeMainPage,
          icon: const Icon(Icons.keyboard_backspace),
        ),
      ),
      body: Padding(
        padding: EdgeInsets.all(16.0),
        child: Center(
          child: Card(
            elevation: 5,
            shape:
                RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
            child: Padding(
              padding: EdgeInsets.all(20.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text("فرم ثبت دانشجو",
                      style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Colors.blueAccent)),
                  SizedBox(height: 20),
                  _buildTextField(
                      controller.nameController, "نام دانشجو", Icons.person),
                  SizedBox(height: 10),
                  _buildTextField(controller.studentNumberController,
                      "شماره دانشجویی", Icons.confirmation_number,
                      keyboardType: TextInputType.number),
                  SizedBox(height: 10),
                  _buildTextField(
                      controller.passwordController, "رمز عبور", Icons.lock,
                      obscureText: true),
                  SizedBox(height: 20),
                  Obx(() => controller.isLoading.value
                      ? CircularProgressIndicator()
                      : SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: controller.addStudent,
                            style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.blueAccent,
                                padding: EdgeInsets.symmetric(vertical: 12),
                                shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(10))),
                            child: Text("ثبت دانشجو",
                                style: TextStyle(
                                    fontSize: 16, color: Colors.white)),
                          ),
                        )),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildTextField(
      TextEditingController controller, String label, IconData icon,
      {bool obscureText = false,
      TextInputType keyboardType = TextInputType.text}) {
    return TextField(
      controller: controller,
      obscureText: obscureText,
      keyboardType: keyboardType,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: Colors.blueAccent),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }
}
