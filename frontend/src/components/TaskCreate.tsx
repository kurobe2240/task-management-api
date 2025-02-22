import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Button,
  TextField,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Box,
  SelectChangeEvent
} from '@mui/material';
import { DateTimePicker } from '@mui/x-date-pickers/DateTimePicker';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDateFns } from '@mui/x-date-pickers/AdapterDateFns';
import { format } from 'date-fns';
import ja from 'date-fns/locale/ja';
import axios from '../lib/axios';

interface TaskCreateProps {
  open: boolean;
  onClose: () => void;
  projectId?: number;
}

const TaskCreate: React.FC<TaskCreateProps> = ({ open = false, onClose = () => {}, projectId }) => {
  const navigate = useNavigate();
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [dueDate, setDueDate] = useState<Date | null>(null);
  const [status, setStatus] = useState('not_started');

  const handleSubmit = async (e: React.MouseEvent<HTMLButtonElement>) => {
    e.preventDefault();
    try {
      await axios.post('/tasks', {
        title,
        description,
        due_date: dueDate ? format(dueDate, 'yyyy-MM-dd HH:mm:ss') : null,
        status,
        project_id: projectId
      });
      onClose();
      navigate('/tasks');
    } catch (error) {
      console.error('タスク作成エラー:', error);
    }
  };

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth>
      <DialogTitle>タスクの作成</DialogTitle>
      <DialogContent>
        <Box sx={{ mt: 2 }}>
          <TextField
            fullWidth
            label="タイトル"
            value={title}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setTitle(e.target.value)}
            margin="normal"
            required
          />
          <TextField
            fullWidth
            label="説明"
            value={description}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setDescription(e.target.value)}
            margin="normal"
            multiline
            rows={4}
          />
          <LocalizationProvider dateAdapter={AdapterDateFns} adapterLocale={ja}>
            <DateTimePicker
              label="期限"
              value={dueDate}
              onChange={(newValue: Date | null) => setDueDate(newValue)}
              format="yyyy/MM/dd HH:mm"
              ampm={false}
              sx={{ mt: 2, width: '100%' }}
              slotProps={{ textField: { fullWidth: true } }}
            />
          </LocalizationProvider>
          <FormControl fullWidth margin="normal">
            <InputLabel>ステータス</InputLabel>
            <Select
              value={status}
              onChange={(e: SelectChangeEvent) => setStatus(e.target.value)}
              label="ステータス"
            >
              <MenuItem value="not_started">未着手</MenuItem>
              <MenuItem value="in_progress">進行中</MenuItem>
              <MenuItem value="completed">完了</MenuItem>
            </Select>
          </FormControl>
        </Box>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose}>キャンセル</Button>
        <Button onClick={handleSubmit} variant="contained" color="primary">
          作成
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default TaskCreate;
