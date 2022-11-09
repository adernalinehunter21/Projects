using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Employee_Wage_Calculator
{
    public partial class log_in : Form
    {
        public log_in()
        {
            InitializeComponent();
        }

        private void log_in_Load(object sender, EventArgs e)
        {

        }

        private void label1_Click(object sender, EventArgs e)
        {

        }

        private void textBox1_TextChanged(object sender, EventArgs e)
        {
           
        }

        private void label2_Click(object sender, EventArgs e)
        {

        }

        private void textBox2_TextChanged(object sender, EventArgs e)
        {

        }

        private void login_Click(object sender, EventArgs e)
        {
            String UserName = username.Text;

            String Password = password.Text;


            if (UserName == "") {
                label3.Text = "Enter Your Username";
            } else if (Password == "") {
                label3.Text = "Enter Your Password";
            } else if (UserName != "admin" && Password == "admin") {
                label3.Text = "Username is not matched";

            }
            else if (UserName == "admin" && Password != "admin")
            {
                label3.Text = "Password is not matched";

            }
            else if (UserName == "admin" && Password == "admin")
            {
                this.Hide();
                Wage wage = new Wage(UserName , Password);

                wage.Show();
                
                
            }
            else {

                label3.Text = "Invalid Credentials";
            
            }
        }

        private void label3_Click(object sender, EventArgs e)
        {

        }
    }
}
